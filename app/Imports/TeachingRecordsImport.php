<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\TeachingRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TeachingRecordsImport implements ToCollection
{
    public int $importedCount = 0;

    public int $updatedCount = 0;

    public int $invalidCount = 0;

    public array $missingHeaders = [];

    private ?array $headers = null;

    public function __construct(private readonly int $teacherId) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if ($this->headers === null) {
                $this->headers = $this->detectHeaders($row);

                continue;
            }

            if (! empty($this->missingHeaders)) {
                return;
            }

            $subjectName = $this->cell($row, 'subject_name');
            $className = $this->cell($row, 'class_name');
            $centerName = $this->cell($row, 'center_name');
            $termCode = $this->cell($row, 'term_code');

            if ($this->isEmptyRow([$subjectName, $className, $centerName, $termCode])) {
                continue;
            }

            if ($subjectName === '') {
                $this->invalidCount++;

                continue;
            }

            $courseId = $this->matchCourseId($subjectName);
            $classId = $className !== '' ? $this->matchClassId($className) : null;
            $status = $this->normalizeStatus($this->cell($row, 'status'));

            $payload = [
                'teacher_id' => $this->teacherId,
                'course_id' => $courseId,
                'class_id' => $classId,
                'subject_name' => $subjectName,
                'class_name' => $className ?: null,
                'center_name' => $centerName ?: null,
                'term_code' => $termCode ?: null,
                'planned_sessions' => max(0, (int) $this->cell($row, 'planned_sessions')),
                'start_date' => $this->parseDate($row[$this->headers['start_date']] ?? null),
                'end_date' => $this->parseDate($row[$this->headers['end_date']] ?? null),
                'status' => $status,
                'note' => $this->cell($row, 'note') ?: null,
            ];

            $record = TeachingRecord::query()
                ->where('teacher_id', $this->teacherId)
                ->where('subject_name', $subjectName)
                ->where(function ($query) use ($className) {
                    $className === ''
                        ? $query->whereNull('class_name')->orWhere('class_name', '')
                        : $query->where('class_name', $className);
                })
                ->where(function ($query) use ($termCode) {
                    $termCode === ''
                        ? $query->whereNull('term_code')->orWhere('term_code', '')
                        : $query->where('term_code', $termCode);
                })
                ->first();

            if ($record) {
                $record->update($payload);
                $this->updatedCount++;

                continue;
            }

            TeachingRecord::create($payload);
            $this->importedCount++;
        }
    }

    private function detectHeaders(Collection $row): array
    {
        $normalized = $row->map(fn ($cell) => $this->normalize((string) $cell));

        $headers = [
            'subject_name' => $this->findHeaderIndex($normalized, ['tenmonhoc', 'monhoc', 'tenkhoahoc']),
            'class_name' => $this->findHeaderIndex($normalized, ['lop', 'lophoc', 'malop']),
            'center_name' => $this->findHeaderIndex($normalized, ['trungtam', 'coso', 'diadiem']),
            'term_code' => $this->findHeaderIndex($normalized, ['khoa', 'khoahoc', 'dot']),
            'planned_sessions' => $this->findHeaderIndex($normalized, ['sobuoi', 'sotiet', 'sobuoihoc']),
            'start_date' => $this->findHeaderIndex($normalized, ['ngaybatdau', 'batdau']),
            'end_date' => $this->findHeaderIndex($normalized, ['ngayketthuc', 'ketthuc']),
            'status' => $this->findHeaderIndex($normalized, ['trangthai', 'tinhtrang']),
            'note' => $this->findHeaderIndex($normalized, ['ghichu', 'note']),
        ];

        $required = ['subject_name', 'class_name', 'center_name', 'term_code', 'planned_sessions', 'start_date', 'end_date', 'status', 'note'];
        $this->missingHeaders = collect($required)
            ->filter(fn ($key) => $headers[$key] === null)
            ->values()
            ->all();

        return $headers;
    }

    private function findHeaderIndex(Collection $cells, array $candidates): ?int
    {
        foreach ($cells as $index => $cell) {
            if (in_array($cell, $candidates, true)) {
                return $index;
            }
        }

        return null;
    }

    private function cell(Collection $row, string $key): string
    {
        $index = $this->headers[$key] ?? null;
        if ($index === null) {
            return '';
        }

        return trim((string) ($row[$index] ?? ''));
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '')->toString();
    }

    private function isEmptyRow(array $values): bool
    {
        return collect($values)->every(fn ($value) => trim((string) $value) === '');
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);
        if ($value === '' || str_contains(Str::lower($value), 'yyyy')) {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeStatus(string $value): string
    {
        $normalized = $this->normalize($value);

        return match (true) {
            str_contains($normalized, 'hoanthanh') => TeachingRecord::STATUS_COMPLETED,
            str_contains($normalized, 'tamhoan') || str_contains($normalized, 'hoan') => TeachingRecord::STATUS_PAUSED,
            str_contains($normalized, 'huy') => TeachingRecord::STATUS_CANCELLED,
            default => TeachingRecord::STATUS_TEACHING,
        };
    }

    private function matchCourseId(string $subjectName): ?int
    {
        return Course::query()
            ->where('teacher_id', $this->teacherId)
            ->where('course_type', 'delivery')
            ->notArchived()
            ->where(function ($query) use ($subjectName) {
                $query->where('title', $subjectName)
                    ->orWhere('title', 'like', "%{$subjectName}%")
                    ->orWhereRaw('? LIKE CONCAT("%", title, "%")', [$subjectName]);
            })
            ->orderByRaw('CASE WHEN title = ? THEN 0 ELSE 1 END', [$subjectName])
            ->value('id');
    }

    private function matchClassId(string $className): ?int
    {
        return Classroom::query()
            ->where('teacher_id', $this->teacherId)
            ->notArchived()
            ->where(function ($query) use ($className) {
                $query->where('name', $className)
                    ->orWhere('code', $className)
                    ->orWhere('name', 'like', "%{$className}%")
                    ->orWhere('code', 'like', "%{$className}%")
                    ->orWhereRaw('? LIKE CONCAT("%", code, "%")', [$className]);
            })
            ->orderByRaw('CASE WHEN code = ? OR name = ? THEN 0 ELSE 1 END', [$className, $className])
            ->value('id');
    }
}
