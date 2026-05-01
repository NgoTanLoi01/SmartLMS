<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Option;
use App\Models\Course;
use Maatwebsite\Excel\Facades\Excel;

class QuestionController extends Controller
{
    // ==========================================
    // 1. HIỂN THỊ GIAO DIỆN NGÂN HÀNG CÂU HỎI
    // ==========================================
    public function index(Request $request)
    {
        $user = auth()->user();

        // Admin thấy tất cả khóa học, Giáo viên chỉ thấy khóa mình dạy
        if ($user->role === 'admin') {
            $courses = Course::all();
            // Thêm 'course.teacher' để load thông tin giáo viên phụ trách khóa học
            $query = Question::with(['course.teacher', 'options']);
        } else {
            $courses = Course::where('teacher_id', $user->id)->get();
            $query = Question::with(['course.teacher', 'options'])->whereIn('course_id', $courses->pluck('id'));
        }

        // Xử lý bộ lọc theo khóa học
        if ($request->has('course_id') && $request->course_id != '') {
            $query->where('course_id', $request->course_id);
        }

        // Lấy danh sách câu hỏi (có phân trang)
        $questions = $query->orderBy('created_at', 'desc')->paginate(15);

        // Giữ lại query string khi chuyển trang
        $questions->appends($request->all());

        return view('quizzes.question_bank', compact('courses', 'questions'));
    }

    // ==========================================
    // 2. THÊM CÂU HỎI VÀO NGÂN HÀNG
    // ==========================================
    public function storeBank(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'question_text' => 'required|string',
            'options' => 'required|array|size:4',
            'correct_option' => 'required|in:1,2,3,4',
        ]);

        $question = Question::create([
            'course_id' => $request->course_id,
            'difficulty' => $request->difficulty,
            'question_text' => $request->question_text,
        ]);

        foreach ($request->options as $index => $optionText) {
            Option::create([
                'question_id' => $question->id,
                'option_text' => $optionText,
                'is_correct' => $index == $request->correct_option ? true : false,
            ]);
        }

        return back()->with('success', 'Đã thêm câu hỏi vào Ngân hàng thành công!');
    }

    // ==========================================
    // 3. CẬP NHẬT CÂU HỎI TRONG NGÂN HÀNG
    // ==========================================
    public function updateBank(Request $request, $id)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'question_text' => 'required|string',
            'options' => 'required|array|size:4',
            'correct_option' => 'required|in:1,2,3,4',
        ]);

        $question = Question::findOrFail($id);

        if (auth()->user()->role !== 'admin' && auth()->id() !== $question->course->teacher_id) {
            abort(403, 'Bạn không có quyền sửa câu hỏi này.');
        }

        $question->update([
            'course_id' => $request->course_id,
            'difficulty' => $request->difficulty,
            'question_text' => $request->question_text,
        ]);

        // Cập nhật các đáp án
        $options = $question->options()->orderBy('id', 'asc')->get();
        $index = 1;
        foreach ($request->options as $optionText) {
            if (isset($options[$index - 1])) {
                $options[$index - 1]->update([
                    'option_text' => $optionText,
                    'is_correct' => $index == $request->correct_option ? true : false,
                ]);
            }
            $index++;
        }

        return back()->with('success', 'Đã cập nhật câu hỏi thành công!');
    }

    // ==========================================
    // 4. XÓA CÂU HỎI KHỎI NGÂN HÀNG
    // ==========================================
    public function destroyBank($id)
    {
        $question = Question::findOrFail($id);

        if (auth()->user()->role !== 'admin' && auth()->id() !== $question->course->teacher_id) {
            abort(403, 'Bạn không có quyền xóa câu hỏi này.');
        }

        $question->delete();

        return back()->with('success', 'Đã xóa câu hỏi khỏi Ngân hàng!');
    }
    // ==========================================
    // 5. IMPORT CÂU HỎI TỪ FILE EXCEL
    // ==========================================
    public function importBank(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'file' => 'required|file|max:5120', // Tạm bỏ "mimes" để chống lỗi chặn file Excel ẩn
        ]);

        try {
            // Khởi tạo Import Class
            $import = new \App\Imports\QuestionImport($request->course_id);

            // Chạy Import
            Excel::import($import, $request->file('file'));

            return back()->with('success', "Thành công! Đã thêm {$import->importedCount} câu hỏi vào Ngân hàng.");
        } catch (\Exception $e) {
            // Nếu có lỗi hệ thống, báo lỗi đỏ ra màn hình để ta biết đường sửa
            return back()->with('error', 'Lỗi khi đọc file: ' . $e->getMessage());
        }
    }
}
