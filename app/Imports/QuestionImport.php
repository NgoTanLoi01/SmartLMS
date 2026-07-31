<?php

namespace App\Imports;

use App\Models\Option;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow; // Thêm thư viện này

class QuestionImport implements ToCollection, WithStartRow
{
    protected $courseId;

    protected $questionBankId;

    public $importedCount = 0; // Biến đếm số câu thành công

    public function __construct($courseId, $questionBankId = null)
    {
        $this->courseId = $courseId;
        $this->questionBankId = $questionBankId;
    }

    // Bắt đầu đọc từ dòng 2 (bỏ qua dòng tiêu đề A1->G1)
    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows)
    {
        $rowNumber = $this->startRow() - 1;

        foreach ($rows as $row) {
            $rowNumber++;
            $cells = collect($row)->values()->map(fn ($value) => trim((string) $value));

            if ($cells->filter(fn ($value) => $value !== '')->isEmpty()) {
                continue;
            }

            $requiredCells = $cells->take(7);
            if ($requiredCells->count() !== 7 || $requiredCells->contains(fn ($value) => $value === '')) {
                $this->rejectRow($rowNumber, 'phải có đủ 7 cột và không được để trống nội dung, độ khó, 4 lựa chọn hoặc đáp án đúng.');
            }

            if ($cells->slice(7)->contains(fn ($value) => $value !== '')) {
                $this->rejectRow($rowNumber, 'chỉ được có đúng 7 cột dữ liệu.');
            }

            $questionText = $cells[0];

            // Cột B: Độ khó.
            $difficulty = strtolower($cells[1]);
            if (! in_array($difficulty, ['easy', 'medium', 'hard'])) {
                $this->rejectRow($rowNumber, 'cột độ khó chỉ nhận easy, medium hoặc hard.');
            }

            // 1. Tạo câu hỏi
            $question = Question::create([
                'course_id' => $this->courseId,
                'question_bank_id' => $this->questionBankId,
                'question_type' => Question::TYPE_SINGLE_CHOICE,
                'difficulty' => $difficulty,
                'question_text' => $questionText,
                'status' => Question::STATUS_PUBLISHED,
            ]);

            // Cột C, D, E, F: Đáp án A, B, C, D.
            $optionsData = [
                'A' => $cells[2],
                'B' => $cells[3],
                'C' => $cells[4],
                'D' => $cells[5],
            ];
            if (count(array_unique(array_map('mb_strtolower', $optionsData))) !== 4) {
                $this->rejectRow($rowNumber, '4 lựa chọn phải khác nhau.');
            }

            // Cột G: Đáp án đúng.
            $correctLetter = strtoupper($cells[6]);
            if (! in_array($correctLetter, ['A', 'B', 'C', 'D'])) {
                $this->rejectRow($rowNumber, 'đáp án đúng chỉ nhận A, B, C hoặc D.');
            }

            // 2. Tạo 4 đáp án vào CSDL
            foreach ($optionsData as $key => $text) {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => $text,
                    'is_correct' => $key === $correctLetter,
                ]);
            }

            $this->importedCount++; // Tăng biến đếm
        }

        if ($this->importedCount === 0) {
            throw ValidationException::withMessages([
                'file' => 'File không có dòng câu hỏi hợp lệ để nhập.',
            ]);
        }
    }

    private function rejectRow(int $rowNumber, string $message): never
    {
        throw ValidationException::withMessages([
            'file' => "Dòng {$rowNumber} {$message}",
        ]);
    }
}
