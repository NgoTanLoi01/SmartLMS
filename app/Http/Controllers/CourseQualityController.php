<?php

namespace App\Http\Controllers;

use App\Models\Assignments;
use App\Models\Course;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseQualityController extends Controller
{
    private const SHORT_LESSON_LIMIT = 220;

    public function check(Course $course)
    {
        $course->load(['modules.lessons.assignments', 'quizzes', 'questionBanks']);
        $this->authorizeManageCourse($course);

        $issues = collect()
            ->merge($this->shortLessonIssues($course))
            ->merge($this->assignmentRubricIssues($course))
            ->merge($this->quizQuestionIssues($course))
            ->merge($this->chatbotContextIssues($course))
            ->merge($this->questionQualityIssues($course))
            ->values();

        $summary = [
            'total' => $issues->count(),
            'high' => $issues->where('severity', 'high')->count(),
            'medium' => $issues->where('severity', 'medium')->count(),
            'low' => $issues->where('severity', 'low')->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'issues' => $issues,
        ]);
    }

    private function shortLessonIssues(Course $course)
    {
        return $course->modules
            ->flatMap->lessons
            ->filter(function ($lesson) {
                $textLength = mb_strlen($this->plainText((string) $lesson->content));

                return $textLength < self::SHORT_LESSON_LIMIT && empty($lesson->video_url) && empty($lesson->attachment);
            })
            ->map(fn ($lesson) => [
                'severity' => 'medium',
                'type' => 'short_lesson',
                'title' => $lesson->title,
                'message' => 'Bài học khá ngắn và chưa có video/tài liệu đính kèm.',
                'suggestion' => 'Bổ sung nội dung chính, ví dụ minh họa hoặc tài liệu để học sinh dễ tự học hơn.',
            ]);
    }

    private function assignmentRubricIssues(Course $course)
    {
        return Assignments::where('course_id', $course->id)
            ->notArchived()
            ->get()
            ->filter(fn ($assignment) => trim((string) $assignment->grading_rubric) === '')
            ->map(fn ($assignment) => [
                'severity' => 'medium',
                'type' => 'missing_rubric',
                'title' => $assignment->title,
                'message' => 'Bài tập chưa có rubric/tiêu chí chấm điểm.',
                'suggestion' => 'Thêm tiêu chí chấm điểm để AI và giáo viên nhận xét nhất quán hơn.',
            ]);
    }

    private function quizQuestionIssues(Course $course)
    {
        $issues = collect();
        $questions = $this->courseQuestions($course);
        $availableByDifficulty = $questions->groupBy('difficulty')->map->count();

        foreach ($course->quizzes as $quiz) {
            $required = [
                'easy' => (int) $quiz->easy_count,
                'medium' => (int) $quiz->medium_count,
                'hard' => (int) $quiz->hard_count,
            ];

            if (array_sum($required) === 0) {
                $issues->push([
                    'severity' => 'high',
                    'type' => 'empty_quiz_config',
                    'title' => $quiz->title,
                    'message' => 'Quiz chưa cấu hình số lượng câu hỏi.',
                    'suggestion' => 'Thiết lập số câu dễ/trung bình/khó trước khi mở cho học sinh.',
                ]);

                continue;
            }

            foreach ($required as $difficulty => $count) {
                $available = (int) ($availableByDifficulty[$difficulty] ?? 0);
                if ($count > $available) {
                    $issues->push([
                        'severity' => 'high',
                        'type' => 'quiz_missing_questions',
                        'title' => $quiz->title,
                        'message' => "Quiz cần {$count} câu {$this->difficultyLabel($difficulty)} nhưng ngân hàng chỉ có {$available} câu.",
                        'suggestion' => 'Bổ sung câu hỏi vào ngân hàng hoặc giảm số câu trong cấu hình quiz.',
                    ]);
                }
            }
        }

        return $issues;
    }

    private function chatbotContextIssues(Course $course)
    {
        $issues = collect();

        if (!$this->hasTrainedDocuments($course->id)) {
            $issues->push([
                'severity' => 'medium',
                'type' => 'missing_chatbot_documents',
                'title' => $course->title,
                'message' => 'Khóa học chưa có tài liệu huấn luyện cho chatbot.',
                'suggestion' => 'Upload tài liệu bài giảng để AI trả lời theo đúng nội dung khóa học hơn.',
            ]);
        }

        $course->modules
            ->flatMap->lessons
            ->filter(fn ($lesson) => trim($this->plainText((string) $lesson->content)) === '' && empty($lesson->attachment))
            ->each(function ($lesson) use ($issues) {
                $issues->push([
                    'severity' => 'low',
                    'type' => 'lesson_missing_context',
                    'title' => $lesson->title,
                    'message' => 'Bài học chưa có nội dung văn bản hoặc tài liệu để AI bám theo.',
                    'suggestion' => 'Thêm mô tả bài học hoặc file tài liệu để chatbot hỗ trợ học sinh tốt hơn.',
                ]);
            });

        return $issues;
    }

    private function questionQualityIssues(Course $course)
    {
        $issues = collect();
        $questions = $this->courseQuestions($course)->load('options');

        $questions
            ->groupBy(fn ($question) => $this->normalize($question->question_text))
            ->filter(fn ($group, $key) => $key !== '' && $group->count() > 1)
            ->each(function ($group) use ($issues) {
                $issues->push([
                    'severity' => 'medium',
                    'type' => 'duplicate_question',
                    'title' => Str::limit($group->first()->question_text, 90),
                    'message' => 'Có câu hỏi trắc nghiệm bị trùng hoặc rất giống nhau.',
                    'suggestion' => 'Gộp, chỉnh lại câu hỏi hoặc chuyển bớt sang mức độ khó khác.',
                ]);
            });

        foreach ($questions as $question) {
            $options = $question->options;
            $correctCount = $options->where('is_correct', true)->count();
            $emptyOptions = $options->filter(fn ($option) => trim((string) $option->option_text) === '')->count();
            $duplicateOptions = $options
                ->groupBy(fn ($option) => $this->normalize($option->option_text))
                ->filter(fn ($group, $key) => $key !== '' && $group->count() > 1)
                ->isNotEmpty();

            if ($options->count() < 4 || $correctCount !== 1 || $emptyOptions > 0 || $duplicateOptions || mb_strlen($this->plainText($question->question_text)) < 20) {
                $issues->push([
                    'severity' => $correctCount !== 1 ? 'high' : 'medium',
                    'type' => 'ambiguous_question',
                    'title' => Str::limit($question->question_text, 90),
                    'message' => 'Câu hỏi có dấu hiệu mơ hồ: thiếu đáp án, số đáp án đúng không chuẩn, đáp án trùng hoặc câu hỏi quá ngắn.',
                    'suggestion' => 'Kiểm tra lại nội dung câu hỏi, đảm bảo có 4 lựa chọn rõ ràng và đúng 1 đáp án đúng.',
                ]);
            }
        }

        return $issues;
    }

    private function courseQuestions(Course $course)
    {
        $bankIds = $course->questionBanks->pluck('id');

        return Question::with('options')
            ->notArchived()
            ->where(function ($query) use ($course, $bankIds) {
                $query->where('course_id', $course->id);

                if ($bankIds->isNotEmpty()) {
                    $query->orWhereIn('question_bank_id', $bankIds);
                }
            })
            ->get();
    }

    private function hasTrainedDocuments(int $courseId): bool
    {
        try {
            return DB::connection('pgsql')
                ->table('document_chunks')
                ->where('course_id', $courseId)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private function authorizeManageCourse(Course $course): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'teacher' && $course->teacher_id === $user->id) {
            return;
        }

        abort(403, 'Bạn không có quyền kiểm tra khóa học này.');
    }

    private function plainText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($this->plainText($text), 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = $ascii !== false ? $ascii : $text;
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';

        return trim($text);
    }

    private function difficultyLabel(string $difficulty): string
    {
        return match ($difficulty) {
            'easy' => 'dễ',
            'hard' => 'khó',
            default => 'trung bình',
        };
    }
}
