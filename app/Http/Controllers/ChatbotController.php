<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ChatbotController extends Controller
{
    protected $deepseekService;

    // Inject DeepSeekService vào qua Constructor
    public function __construct(DeepSeekService $deepseekService)
    {
        $this->deepseekService = $deepseekService;
    }

    /**
     * Hàm duy nhất xử lý gửi tin nhắn
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:30'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:4000'],
            'lesson_context' => ['nullable', 'array'],
            'lesson_context.course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'lesson_context.lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'lesson_context.assist_mode' => ['nullable', 'string', 'max:100'],
        ]);

        if (($validated['messages'][array_key_last($validated['messages'])]['role'] ?? null) !== 'user') {
            throw ValidationException::withMessages([
                'messages' => 'Tin nhắn cuối cùng phải do người dùng gửi.',
            ]);
        }

        $lessonContext = $validated['lesson_context'] ?? [];
        $options = [];
        if (is_array($lessonContext)) {
            $requestedCourseId = ! empty($lessonContext['course_id'])
                ? (int) $lessonContext['course_id']
                : null;

            if (! empty($lessonContext['lesson_id'])) {
                $lesson = Lesson::query()
                    ->with('module.course')
                    ->findOrFail((int) $lessonContext['lesson_id']);
                $contextCourse = $lesson->module?->course;
                abort_unless($contextCourse, 404);
                Gate::authorize('view', $contextCourse);

                if ($requestedCourseId !== null && $requestedCourseId !== (int) $contextCourse->id) {
                    throw ValidationException::withMessages([
                        'lesson_context.course_id' => 'Khóa học không khớp với bài học đang chọn.',
                    ]);
                }

                $options['course_id'] = (int) $contextCourse->id;
                $options['lesson_id'] = (int) $lesson->id;
                $options['assist_mode'] = (string) ($lessonContext['assist_mode'] ?? '');
            } elseif ($requestedCourseId !== null) {
                $contextCourse = Course::findOrFail($requestedCourseId);
                Gate::authorize('view', $contextCourse);
                $options['course_id'] = (int) $contextCourse->id;
            }
        }

        try {
            $messages = array_slice($validated['messages'], -12);

            // Chatbot tìm ngữ cảnh theo quyền truy cập khóa học của người dùng hiện tại.
            $reply = $this->deepseekService->sendMessage($messages, $request->user(), $options);

            return response()->json([
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            Log::error('CHATBOT_ERROR: '.$e->getMessage());

            return response()->json(
                [
                    'reply' => 'Dạ, hệ thống đang gặp chút sự cố kỹ thuật. Thầy Lợi đang kiểm tra lại ạ!',
                ],
                500,
            );
        }
    }
}
