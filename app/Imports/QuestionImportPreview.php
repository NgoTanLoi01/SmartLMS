<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;

class QuestionImportPreview implements ToCollection, WithChunkReading, WithStartRow
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $processedRows = 0;

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows->values() as $row) {
            $rowNumber = $this->processedRows + $this->startRow();
            $this->processedRows++;
            if ($this->processedRows > 500) {
                throw ValidationException::withMessages([
                    'file' => 'Mỗi lần chỉ được xem trước tối đa 500 dòng câu hỏi.',
                ]);
            }
            $cells = collect($row)->values()->map(fn ($value) => trim((string) $value));
            if ($cells->filter()->isEmpty()) {
                continue;
            }

            $requiredCells = $cells->take(7);
            if ($requiredCells->count() !== 7 || $requiredCells->contains(fn ($value) => $value === '')) {
                $this->rejectRow($rowNumber, 'phải có đủ 7 cột bắt buộc và không được để trống.');
            }
            if ($cells->slice(8)->contains(fn ($value) => $value !== '')) {
                $this->rejectRow($rowNumber, 'chỉ được có tối đa 8 cột dữ liệu.');
            }

            $difficulty = Str::lower($cells[1]);
            if (! in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
                $this->rejectRow($rowNumber, 'cột độ khó chỉ nhận easy, medium hoặc hard.');
            }

            $options = $cells->slice(2, 4)->values()->all();
            if (count(array_unique(array_map(fn ($value) => Str::lower($value), $options))) !== 4) {
                $this->rejectRow($rowNumber, '4 lựa chọn phải khác nhau.');
            }

            $correctLetter = Str::upper($cells[6]);
            if (! in_array($correctLetter, ['A', 'B', 'C', 'D'], true)) {
                $this->rejectRow($rowNumber, 'đáp án đúng chỉ nhận A, B, C hoặc D.');
            }

            $tags = collect(preg_split('/[,;]+/u', (string) $cells->get(7, '')) ?: [])
                ->map(fn ($tag) => trim((string) $tag))
                ->filter()
                ->unique(fn ($tag) => Str::lower($tag))
                ->values();
            if ($tags->count() > 20 || $tags->contains(fn ($tag) => mb_strlen($tag) > 50)) {
                $this->rejectRow($rowNumber, 'chỉ nhận tối đa 20 tag, mỗi tag không quá 50 ký tự.');
            }

            $this->rows[] = [
                'row_number' => $rowNumber,
                'question_text' => $cells[0],
                'difficulty' => $difficulty,
                'options' => $options,
                'correct_letter' => $correctLetter,
                'tags' => $tags->all(),
            ];
        }

        if ($this->rows === []) {
            throw ValidationException::withMessages([
                'file' => 'File không có dòng câu hỏi hợp lệ để xem trước.',
            ]);
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }

    private function rejectRow(int $rowNumber, string $message): never
    {
        throw ValidationException::withMessages([
            'file' => "Dòng {$rowNumber} {$message}",
        ]);
    }
}
