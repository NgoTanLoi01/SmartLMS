<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\Question;
use App\Services\NotificationCenter;

class QuizController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'time_limit' => 'required|integer|min:1',
            'easy_count' => 'required|integer|min:0',
            'medium_count' => 'required|integer|min:0',
            'hard_count' => 'required|integer|min:0',
            'status' => 'nullable|in:draft,published,hidden,archived',
            'available_from' => 'nullable|date',
        ]);

        $quiz = Quiz::create([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'time_limit' => $request->time_limit,
            'is_random' => true,
            'easy_count' => $request->easy_count,
            'medium_count' => $request->medium_count,
            'hard_count' => $request->hard_count,
            'status' => $request->input('status', 'published'),
            'published_at' => $request->input('status', 'published') === 'published' ? now() : null,
            'available_from' => $request->available_from,
        ]);

        if ($quiz->status === Quiz::STATUS_PUBLISHED) {
            app(NotificationCenter::class)->notifyCourseStudents(
                $quiz->course_id,
                'quiz',
                'Có bài kiểm tra mới',
                "Bài kiểm tra \"{$quiz->title}\" vừa được đăng.",
                route('courses.show', $quiz->course_id),
                ['quiz_id' => $quiz->id],
                "quiz:{$quiz->id}:published"
            );
        }

        return back()->with('success', 'Đã tạo cấu hình bài kiểm tra ngẫu nhiên thành công!');
    }

    public function show($id)
    {
        $quiz = Quiz::findOrFail($id);

        // Kiểm tra quyền (chỉ giáo viên của khóa học, học sinh của khóa, hoặc admin mới được xem)
        // ... (Giữ nguyên logic phân quyền của bạn nếu có)

        // ==========================================
        // THUẬT TOÁN LẤY ĐỀ NGẪU NHIÊN & XÁO TRỘN
        // ==========================================
        if ($quiz->is_random) {
            $bankIds = $quiz->course->questionBanks()->pluck('question_banks.id');
            $pick = fn ($difficulty, $limit) => Question::with('options')
                ->notArchived()
                ->where(function ($q) use ($quiz, $bankIds) {
                    if ($bankIds->isNotEmpty()) {
                        $q->whereIn('question_bank_id', $bankIds);
                    }

                    $q->orWhere('course_id', $quiz->course_id);
                })
                ->where('difficulty', $difficulty)
                ->inRandomOrder()
                ->limit($limit)
                ->get();

            // 1. Bốc ngẫu nhiên câu hỏi từ Ngân hàng theo độ khó
            $easyQuestions = $pick('easy', $quiz->easy_count);

            $mediumQuestions = $pick('medium', $quiz->medium_count);

            $hardQuestions = $pick('hard', $quiz->hard_count);

            // 2. Gộp tất cả lại thành 1 đề thi duy nhất
            $examQuestions = $easyQuestions->merge($mediumQuestions)->merge($hardQuestions);

            // 3. Xáo trộn thứ tự các CÂU HỎI
            $examQuestions = $examQuestions->shuffle();

            // 4. Xáo trộn thứ tự các ĐÁP ÁN bên trong mỗi câu hỏi
            foreach ($examQuestions as $question) {
                // Biến Collection options thành một Collection mới đã xáo trộn
                $question->setRelation('options', $question->options->shuffle());
            }
        }

        // Truyền $examQuestions ra View (thay vì $quiz->questions như trước kia)
        return view('quizzes.show', compact('quiz', 'examQuestions'));
    }

    public function destroy($id)
    {
        Quiz::findOrFail($id)->update([
            'status' => Quiz::STATUS_ARCHIVED,
            'published_at' => null,
        ]);

        return back()->with('success', 'Đã lưu trữ bài kiểm tra. Lịch sử làm bài và điểm số vẫn được giữ lại.');
    }

    public function submissions($id)
    {
        $quiz = Quiz::with(['course.teacher', 'attempts.user'])->findOrFail($id);

        if (auth()->id() !== $quiz->course->teacher_id && auth()->user()->role !== 'admin') {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        $attempts = $quiz->attempts()->orderBy('completed_at', 'desc')->get();
        return view('quizzes.submissions', compact('quiz', 'attempts'));
    }
}
