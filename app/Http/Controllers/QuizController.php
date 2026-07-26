<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\NotificationCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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

        $course = Course::findOrFail($request->integer('course_id'));
        Gate::authorize('create', [Quiz::class, $course]);

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
        Gate::authorize('view', $quiz);

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
        $quiz = Quiz::findOrFail($id);
        Gate::authorize('delete', $quiz);
        $quiz->update([
            'status' => Quiz::STATUS_ARCHIVED,
            'published_at' => null,
        ]);

        return back()->with('success', 'Đã lưu trữ bài kiểm tra. Lịch sử làm bài và điểm số vẫn được giữ lại.');
    }

    public function archived(Course $course)
    {
        Gate::authorize('manageContent', $course);
        $archivedQuizzes = Quiz::query()
            ->where('course_id', $course->id)
            ->where('status', Quiz::STATUS_ARCHIVED)
            ->withCount(['sessions', 'attempts'])
            ->latest('updated_at')
            ->get();

        return view('quizzes.archived', compact('course', 'archivedQuizzes'));
    }

    public function restore($id)
    {
        $quiz = Quiz::where('status', Quiz::STATUS_ARCHIVED)->findOrFail($id);
        Gate::authorize('update', $quiz);
        $quiz->update([
            'status' => Quiz::STATUS_DRAFT,
            'published_at' => null,
        ]);

        return redirect()->route('courses.show', $quiz->course_id)
            ->with('success', 'Đã khôi phục bài kiểm tra về trạng thái bản nháp.');
    }

    public function forceDestroy(Request $request, $id)
    {
        $quiz = Quiz::where('status', Quiz::STATUS_ARCHIVED)->findOrFail($id);
        Gate::authorize('delete', $quiz);
        $request->validate(['confirmation' => ['required', 'string']]);

        if (! hash_equals($quiz->title, (string) $request->confirmation)) {
            throw ValidationException::withMessages([
                'confirmation' => 'Tên bài kiểm tra xác nhận không chính xác.',
            ]);
        }

        if ($quiz->attempts()->exists()) {
            return back()->with('error', 'Không thể xóa vĩnh viễn vì bài kiểm tra đã có bài làm. Bạn có thể tiếp tục giữ trong kho lưu trữ.');
        }

        $courseId = $quiz->course_id;
        DB::transaction(fn () => $quiz->delete());

        return redirect()->route('quizzes.archived', $courseId)
            ->with('success', 'Đã xóa vĩnh viễn bài kiểm tra và các ca thi chưa phát sinh bài làm.');
    }

    public function submissions($id)
    {
        $quiz = Quiz::with(['course.teacher', 'attempts.user', 'attempts.session'])->findOrFail($id);
        Gate::authorize('viewSubmissions', $quiz);

        $attempts = $quiz->attempts()->orderBy('completed_at', 'desc')->get();

        return view('quizzes.submissions', compact('quiz', 'attempts'));
    }
}
