<?php

namespace App\Imports;

use App\Models\Option;
use App\Models\Question;
use Illuminate\Support\Collection;
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
        foreach ($rows as $row) {
            // Kiểm tra: Bỏ qua nếu cột Nội dung câu hỏi (Cột A) bị rỗng
            if (! isset($row[0]) || trim($row[0]) === '') {
                continue;
            }

            $questionText = trim($row[0]);

            // Cột 1: Độ khó (Mặc định là medium nếu bỏ trống)
            $difficulty = isset($row[1]) ? strtolower(trim($row[1])) : 'medium';
            if (! in_array($difficulty, ['easy', 'medium', 'hard'])) {
                $difficulty = 'medium';
            }

            // 1. Tạo câu hỏi
            $question = Question::create([
                'course_id' => $this->courseId,
                'question_bank_id' => $this->questionBankId,
                'difficulty' => $difficulty,
                'question_text' => $questionText,
                'status' => Question::STATUS_PUBLISHED,
            ]);

            // Cột 2, 3, 4, 5: Đáp án A, B, C, D (Nếu trống thì gán mặc định)
            $optionsData = [
                'A' => isset($row[2]) && trim($row[2]) !== '' ? trim($row[2]) : 'Đáp án A',
                'B' => isset($row[3]) && trim($row[3]) !== '' ? trim($row[3]) : 'Đáp án B',
                'C' => isset($row[4]) && trim($row[4]) !== '' ? trim($row[4]) : 'Đáp án C',
                'D' => isset($row[5]) && trim($row[5]) !== '' ? trim($row[5]) : 'Đáp án D',
            ];

            // Cột 6: Đáp án đúng (G)
            $correctLetter = isset($row[6]) ? strtoupper(trim($row[6])) : 'A';
            if (! in_array($correctLetter, ['A', 'B', 'C', 'D'])) {
                $correctLetter = 'A'; // Chống lỗi: Nếu nhập sai, mặc định A là đúng
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
    }
}
