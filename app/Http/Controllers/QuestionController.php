<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Option;
use App\Models\Quiz;

class QuestionController extends Controller
{
    public function store(Request $request, $quiz_id)
    {
        $request->validate([
            'question_text' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'correct_option' => 'required|in:A,B,C,D', // Phải chọn đáp án đúng
        ]);

        // 1. Lưu câu hỏi vào bảng questions
        $question = Question::create([
            'quiz_id' => $quiz_id,
            'question_text' => $request->question_text,
        ]);

        // 2. Lưu 4 đáp án vào bảng options
        $options = [
            'A' => $request->option_a,
            'B' => $request->option_b,
            'C' => $request->option_c,
            'D' => $request->option_d,
        ];

        foreach ($options as $key => $optionText) {
            Option::create([
                'question_id' => $question->id,
                'option_text' => $optionText,
                'is_correct' => $key === $request->correct_option ? true : false,
            ]);
        }

        return back()->with('success', 'Đã thêm câu hỏi thành công!');
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'question_text' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'correct_option' => 'required|in:A,B,C,D',
        ]);

        $question = Question::findOrFail($id);

        // 1. Cập nhật nội dung câu hỏi
        $question->update(['question_text' => $request->question_text]);

        // 2. Cập nhật 4 đáp án (Lấy danh sách options hiện có của câu hỏi này)
        $options = $question->options()->orderBy('id', 'asc')->get();

        $inputOptions = [
            'A' => $request->option_a,
            'B' => $request->option_b,
            'C' => $request->option_c,
            'D' => $request->option_d,
        ];

        $index = 0;
        foreach ($inputOptions as $key => $text) {
            if (isset($options[$index])) {
                $options[$index]->update([
                    'option_text' => $text,
                    'is_correct' => $key === $request->correct_option,
                ]);
            }
            $index++;
        }

        return back()->with('success', 'Đã cập nhật câu hỏi thành công!');
    }

    public function destroy($id)
    {
        $question = Question::findOrFail($id);
        $question->delete(); // Xóa câu hỏi (các option sẽ tự động xóa theo nhờ onDelete('cascade'))

        return back()->with('success', 'Đã xóa câu hỏi!');
    }
}
