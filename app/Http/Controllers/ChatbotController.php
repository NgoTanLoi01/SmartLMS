<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DeepSeekService;
use Illuminate\Support\Facades\Log;

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
        try {
            // Kiểm tra mảng messages gửi từ chatbot.blade.php
            $messages = $request->input('messages');

            if (!$messages || !is_array($messages)) {
                return response()->json(['reply' => 'Dữ liệu tin nhắn không hợp lệ.'], 400);
            }

            // Chatbot tìm ngữ cảnh theo quyền truy cập khóa học của người dùng hiện tại.
            $reply = $this->deepseekService->sendMessage($messages, $request->user());

            return response()->json([
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            Log::error('CHATBOT_ERROR: ' . $e->getMessage());

            return response()->json(
                [
                    'reply' => 'Dạ, hệ thống đang gặp chút sự cố kỹ thuật. Thầy Lợi đang kiểm tra lại ạ!',
                    'error_detail' => $e->getMessage(), 
                ],
                500,
            );
        }
    }
}
