<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class SafeSpreadsheet implements ValidationRule
{
    private const EXTENSIONS = ['xlsx', 'xls', 'csv'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('File tải lên không hợp lệ.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        if (! in_array($extension, self::EXTENSIONS, true)) {
            $fail('Chỉ chấp nhận file .xlsx, .xls hoặc .csv.');

            return;
        }

        $path = $value->getRealPath();
        $handle = $path ? @fopen($path, 'rb') : false;
        $signature = $handle ? (string) fread($handle, 8) : '';
        if (is_resource($handle)) {
            fclose($handle);
        }

        $valid = match ($extension) {
            'xlsx' => $this->isValidXlsx($path, $signature),
            'xls' => str_starts_with($signature, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"),
            'csv' => $this->isValidCsv($path, $signature),
            default => false,
        };

        if (! $valid) {
            $fail('Nội dung file không khớp với định dạng bảng tính đã chọn.');
        }
    }

    private function isValidXlsx(string|false $path, string $signature): bool
    {
        if (! $path || ! str_starts_with($signature, 'PK')) {
            return false;
        }

        if (! class_exists(ZipArchive::class)) {
            return false;
        }

        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            return false;
        }

        $valid = $archive->locateName('[Content_Types].xml') !== false
            && $archive->locateName('xl/workbook.xml') !== false;
        $archive->close();

        return $valid;
    }

    private function isValidCsv(string|false $path, string $signature): bool
    {
        if (! $path || str_contains($signature, "\0")) {
            return false;
        }

        $sample = (string) @file_get_contents($path, false, null, 0, 8192);

        return $sample !== ''
            && mb_detect_encoding($sample, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true) !== false
            && preg_match('/[,;\t]/', $sample) === 1;
    }
}
