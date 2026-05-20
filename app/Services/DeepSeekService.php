<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeepSeekService
{
    public function sendMessage(array $messages): string
    {
        try {
            // Lấy nội dung câu hỏi cuối cùng của học sinh để đi tìm tài liệu liên quan
            $lastUserMessage = end($messages)['content'];

            $queryVector = $this->getGeminiEmbedding($lastUserMessage);
            $context = '';

            if ($queryVector) {
                $context = $this->findSimilarChunks($queryVector);
            }

            return $this->askDeepSeek($messages, $context);
        } catch (\Exception $e) {
            Log::error('Lỗi quy trình Chatbot: ' . $e->getMessage());
            return 'Không thể kết nối đến máy chủ AI.';
        }
    }

    private function getGeminiEmbedding(string $text): ?array
    {
        try {
            $apiKey = env('GOOGLE_API_KEY');
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key={$apiKey}", [
                    'model' => 'models/gemini-embedding-001',
                    'content' => ['parts' => [['text' => $text]]],
                ]);
            return $response->successful() ? $response->json('embedding.values') : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function findSimilarChunks(array $queryVector, int $limit = 3): string
    {
        $vectorString = '[' . implode(',', $queryVector) . ']';
        $results = DB::connection('pgsql')->select('SELECT content FROM document_chunks ORDER BY embedding <=> ?::vector LIMIT ?', [$vectorString, $limit]);
        return collect($results)->pluck('content')->implode("\n\n---\n\n");
    }

    private function askDeepSeek(array $historyMessages, string $context): string
    {
        $apiKey = config('services.deepseek.key');
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

        $systemContent = "Bạn là trợ lý AI học tập của hệ thống SmartLMS. Hãy trả lời thân thiện.\n\n";
        if (!empty($context)) {
            $systemContent .= "Dựa vào dữ liệu của hệ thống SmartLMS để trả lời:\n" . $context;
        }

        // Tạo danh sách tin nhắn cho API DeepSeek
        $finalMessages = [['role' => 'system', 'content' => $systemContent]];

        // Chuyển đổi role 'assistant' (nếu có từ JS) thành 'assistant' chuẩn API
        foreach ($historyMessages as $msg) {
            $finalMessages[] = [
                'role' => $msg['role'] === 'assistant' ? 'assistant' : $msg['role'],
                'content' => $msg['content'],
            ];
        }

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->withoutVerifying()
            ->post("{$baseUrl}/chat/completions", [
                'model' => 'deepseek-v4-flash',
                'messages' => $finalMessages,
                'temperature' => 0.3,
            ]);

        return $response->successful() ? $response->json('choices.0.message.content') : 'AI đang bận, thử lại sau nhé!';
    }
}
