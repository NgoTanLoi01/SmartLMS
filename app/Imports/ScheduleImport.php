<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ScheduleImport implements ToCollection
{
    public int $importedCount = 0;

    public int $duplicateCount = 0;

    public int $invalidCount = 0;

    public array $unmatchedSubjects = [];

    public array $unmatchedClasses = [];

    private ?array $headers = null;

    private Collection $classrooms;

    public function __construct(
        private readonly ?int $defaultClassId = null,
        private readonly ?int $defaultCourseId = null,
        private readonly array $allowedClassIds = []
    ) {
        $query = Classroom::with(['courses' => fn ($query) => $query->notArchived()])
            ->notArchived();

        if (! empty($this->allowedClassIds)) {
            $query->whereIn('id', $this->allowedClassIds);
        }

        $this->classrooms = $query->get();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if ($this->headers === null) {
                $this->headers = $this->detectHeaders($row);

                continue;
            }

            $dateValue = $row[$this->headers['date']] ?? null;
            $timeValue = $row[$this->headers['time']] ?? null;
            $subjectValue = $row[$this->headers['subject']] ?? null;
            $roomValue = $this->headers['room'] !== null ? ($row[$this->headers['room']] ?? null) : null;
            $classValue = $this->headers['class'] !== null ? ($row[$this->headers['class']] ?? null) : null;

            if ($this->isEmptyScheduleRow($dateValue, $timeValue, $subjectValue)) {
                continue;
            }

            $scheduleDate = $this->parseDate($dateValue);
            $timeRange = $this->parseTimeRange($timeValue);
            $classrooms = $this->resolveClassrooms($classValue);

            if (! $scheduleDate || ! $timeRange || $classrooms->isEmpty()) {
                $this->invalidCount++;

                if ($classrooms->isEmpty() && trim((string) $classValue) !== '') {
                    $this->unmatchedClasses[] = trim((string) $classValue);
                }

                continue;
            }

            $room = trim((string) $roomValue);
            $room = $room !== '' ? $room : null;
            $note = $this->extractNote((string) $subjectValue);

            foreach ($classrooms as $classroom) {
                $courseId = $this->resolveCourseId((string) $subjectValue, $classroom);

                if (! $courseId) {
                    $this->invalidCount++;

                    if (trim((string) $subjectValue) !== '') {
                        $this->unmatchedSubjects[] = trim((string) $subjectValue).' / '.$classroom->name;
                    }

                    continue;
                }

                $existingSchedule = Schedule::query()
                    ->notArchived()
                    ->where('class_id', $classroom->id)
                    ->where('course_id', $courseId)
                    ->whereDate('schedule_date', $scheduleDate)
                    ->where('start_time', $timeRange['start'])
                    ->where('end_time', $timeRange['end'])
                    ->where(function ($query) use ($room) {
                        $room === null ? $query->whereNull('room') : $query->where('room', $room);
                    })
                    ->first();

                if ($existingSchedule) {
                    if ($note && $existingSchedule->note !== $note) {
                        $this->clearCourseExamNote($classroom->id, $courseId, $existingSchedule->id);
                        $existingSchedule->update(['note' => $note]);
                    }

                    $this->duplicateCount++;

                    continue;
                }

                if ($note) {
                    $this->clearCourseExamNote($classroom->id, $courseId);
                }

                Schedule::create([
                    'class_id' => $classroom->id,
                    'course_id' => $courseId,
                    'schedule_date' => $scheduleDate,
                    'start_time' => $timeRange['start'],
                    'end_time' => $timeRange['end'],
                    'room' => $room,
                    'note' => $note,
                    'status' => Schedule::STATUS_ACTIVE,
                ]);

                $this->importedCount++;
            }
        }
    }

    private function detectHeaders(Collection $row): ?array
    {
        $normalizedCells = $row->map(fn ($cell) => $this->normalize((string) $cell));

        $dateIndex = $this->findHeaderIndex($normalizedCells, ['ngay', 'ngayhoc']);
        $timeIndex = $this->findHeaderIndex($normalizedCells, ['giohoc', 'thoigian']);
        $subjectIndex = $this->findHeaderIndex($normalizedCells, ['tenmonhoc', 'monhoc', 'khoahoc']);

        if ($dateIndex === null || $timeIndex === null || $subjectIndex === null) {
            return null;
        }

        return [
            'date' => $dateIndex,
            'time' => $timeIndex,
            'subject' => $subjectIndex,
            'class' => $this->findHeaderIndex($normalizedCells, ['lop', 'lophoc', 'malop']),
            'room' => $this->findHeaderIndex($normalizedCells, ['phonghoc', 'phong']),
        ];
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

    private function isEmptyScheduleRow(mixed $dateValue, mixed $timeValue, mixed $subjectValue): bool
    {
        return trim((string) $dateValue) === ''
            && trim((string) $timeValue) === ''
            && trim((string) $subjectValue) === '';
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
        if ($value === '') {
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

    private function parseTimeRange(mixed $value): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = Str::lower($value);
        $normalized = str_replace(['–', '—', 'đến', 'toi', 'to'], '-', $normalized);
        $normalized = preg_replace('/\s+/', '', $normalized);

        if (! preg_match('/(\d{1,2})(?:[:ghh](\d{1,2}))?-(\d{1,2})(?:[:ghh](\d{1,2}))?/', $normalized, $matches)) {
            return null;
        }

        $start = $this->formatTime((int) $matches[1], (int) ($matches[2] ?? 0));
        $end = $this->formatTime((int) $matches[3], (int) ($matches[4] ?? 0));

        if (! $start || ! $end || $end <= $start) {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }

    private function formatTime(int $hour, int $minute): ?string
    {
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d:00', $hour, $minute);
    }

    private function resolveClassrooms(mixed $classValue): Collection
    {
        $classNames = $this->splitClassNames((string) $classValue);

        if (empty($classNames) && $this->defaultClassId) {
            return $this->classrooms->where('id', $this->defaultClassId)->values();
        }

        if (empty($classNames)) {
            return collect();
        }

        return collect($classNames)
            ->flatMap(fn ($className) => $this->matchClassrooms($className))
            ->unique('id')
            ->values();
    }

    private function splitClassNames(string $value): array
    {
        return collect(preg_split('/[;,\n\r]+/', $value))
            ->map(fn ($className) => trim($className))
            ->filter()
            ->values()
            ->all();
    }

    private function matchClassrooms(string $className): Collection
    {
        $normalizedClassName = $this->normalize($className);

        return $this->classrooms->filter(function ($classroom) use ($normalizedClassName) {
            $code = $this->normalize((string) $classroom->code);
            $name = $this->normalize((string) $classroom->name);

            return $normalizedClassName !== ''
                && (
                    $code === $normalizedClassName
                    || $name === $normalizedClassName
                    || ($code !== '' && str_contains($name, $code) && str_contains($normalizedClassName, $code))
                    || str_contains($name, $normalizedClassName)
                    || str_contains($normalizedClassName, $name)
                );
        })->values();
    }

    private function resolveCourseId(string $subject, Classroom $classroom): ?int
    {
        $subject = $this->normalize($subject);
        $baseSubject = $this->normalizeCourseName($subject);

        if ($subject === '') {
            return $this->defaultCourseForClass($classroom);
        }

        foreach ($classroom->courses as $course) {
            $title = $this->normalize($course->title);
            $baseTitle = $this->normalizeCourseName($title);

            if ($title !== '' && (
                $title === $subject
                || str_contains($subject, $title)
                || str_contains($title, $subject)
                || ($baseSubject !== '' && $baseTitle !== '' && (
                    $baseTitle === $baseSubject
                    || str_contains($baseSubject, $baseTitle)
                    || str_contains($baseTitle, $baseSubject)
                ))
            )) {
                return $course->id;
            }
        }

        return $this->defaultCourseForClass($classroom);
    }

    private function defaultCourseForClass(Classroom $classroom): ?int
    {
        if (! $this->defaultCourseId) {
            return null;
        }

        return $classroom->courses->contains('id', $this->defaultCourseId) ? $this->defaultCourseId : null;
    }

    private function normalize(string $value): string
    {
        return Str::of(preg_replace('/\s+/', ' ', trim($value)))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();
    }

    private function normalizeCourseName(string $normalizedValue): string
    {
        return Str::of($normalizedValue)
            ->replaceMatches('/t\d{2}[a-z]{2}\d{2}\d{2}c/', '')
            ->replaceMatches('/t\d{2}[a-z]{2}\d{2}c/', '')
            ->replaceMatches('/\d+buoi/', '')
            ->replace('thiketthucmon', '')
            ->trim()
            ->toString();
    }

    private function extractNote(string $subject): ?string
    {
        return str_contains($this->normalize($subject), 'thiketthucmon') ? 'Thi kết thúc môn' : null;
    }

    private function clearCourseExamNote(int $classId, int $courseId, ?int $exceptScheduleId = null): void
    {
        Schedule::query()
            ->where('class_id', $classId)
            ->where('course_id', $courseId)
            ->where('note', 'Thi kết thúc môn')
            ->when($exceptScheduleId, fn ($query) => $query->where('id', '!=', $exceptScheduleId))
            ->update(['note' => null]);
    }
}
