<?php

namespace App\Http\Controllers;

use App\Services\DeepSeekService;
use Illuminate\Http\Request;
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
            'lesson_context.lesson_id' => ['nullable', 'integer'],
            'lesson_context.assist_mode' => ['nullable', 'string', 'max:100'],
        ]);

        if (($validated['messages'][array_key_last($validated['messages'])]['role'] ?? null) !== 'user') {
            throw ValidationException::withMessages([
                'messages' => 'Tin nhắn cuối cùng phải do người dùng gửi.',
            ]);
        }

        try {
            $messages = array_slice($validated['messages'], -12);

            $lessonContext = $validated['lesson_context'] ?? [];
            $options = [];
            if (is_array($lessonContext) && ! empty($lessonContext['lesson_id'])) {
                $options['lesson_id'] = (int) $lessonContext['lesson_id'];
                $options['assist_mode'] = (string) ($lessonContext['assist_mode'] ?? '');
            }

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
