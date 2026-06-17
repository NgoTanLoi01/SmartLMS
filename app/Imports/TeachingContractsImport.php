<?php

namespace App\Imports;

use App\Models\TeachingContract;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TeachingContractsImport implements ToCollection
{
    public int $importedCount = 0;
    public int $updatedCount = 0;
    public int $invalidCount = 0;
    public array $missingHeaders = [];

    private ?array $headers = null;

    public function __construct(private readonly int $teacherId)
    {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if ($this->headers === null) {
                $this->headers = $this->detectHeaders($row);
                continue;
            }

            if (!empty($this->missingHeaders)) {
                return;
            }

            $contractNumber = $this->cell($row, 'contract_number');
            if ($contractNumber === '') {
                if (!$this->isEmptyRow($row)) {
                    $this->invalidCount++;
                }
                continue;
            }

            $totalAmount = $this->parseMoney($row[$this->headers['total_amount']] ?? 0);
            $status = $this->normalizeStatus($this->cell($row, 'status'));
            $contractLabel = $this->cell($row, 'contract_label');
            $note = $this->cell($row, 'note');

            if ($contractLabel !== '') {
                $note = trim($note . "\nHợp đồng: " . $contractLabel);
            }

            $payload = [
                'teacher_id' => $this->teacherId,
                'contract_number' => $contractNumber,
                'signed_date' => $this->parseDate($row[$this->headers['signed_date']] ?? null),
                'total_amount' => $totalAmount,
                'received_amount' => $status === TeachingContract::STATUS_RECEIVED ? $totalAmount : 0,
                'status' => $status,
                'received_date' => $this->parseDate($row[$this->headers['received_date']] ?? null),
                'note' => $note !== '' ? $note : null,
            ];

            $contract = TeachingContract::query()
                ->where('contract_number', $contractNumber)
                ->first();

            if ($contract) {
                if ($contract->teacher_id !== $this->teacherId) {
                    $this->invalidCount++;
                    continue;
                }

                $contract->update($payload);
                $this->updatedCount++;
                continue;
            }

            TeachingContract::create($payload);
            $this->importedCount++;
        }
    }

    private function detectHeaders(Collection $row): array
    {
        $normalized = $row->map(fn ($cell) => $this->normalize((string) $cell));

        $headers = [
            'contract_number' => $this->findHeaderIndex($normalized, ['sohopdong', 'mahopdong']),
            'signed_date' => $this->findHeaderIndex($normalized, ['ngayky']),
            'total_amount' => $this->findHeaderIndex($normalized, ['tongtienvnd', 'tongtien', 'sotien']),
            'status' => $this->findHeaderIndex($normalized, ['trangthai', 'tinhtrang']),
            'received_date' => $this->findHeaderIndex($normalized, ['ngaynhan']),
            'contract_label' => $this->findHeaderIndex($normalized, ['hopdong', 'filehopdong', 'linkhopdong']),
            'note' => $this->findHeaderIndex($normalized, ['ghichu', 'note']),
        ];

        $required = ['contract_number', 'signed_date', 'total_amount', 'status', 'received_date', 'contract_label', 'note'];
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

    private function isEmptyRow(Collection $row): bool
    {
        return $row->every(fn ($value) => trim((string) $value) === '');
    }

    private function parseMoney(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = preg_replace('/[^0-9,.-]/', '', (string) $value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : 0;
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
            str_contains($normalized, 'danhan') || str_contains($normalized, 'nhanroi') => TeachingContract::STATUS_RECEIVED,
            str_contains($normalized, 'motphan') || str_contains($normalized, 'partial') => TeachingContract::STATUS_PARTIAL,
            str_contains($normalized, 'huy') => TeachingContract::STATUS_CANCELLED,
            default => TeachingContract::STATUS_UNPAID,
        };
    }
}
