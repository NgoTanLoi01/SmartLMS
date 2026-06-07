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

    public function analyzeLearning(array $payload): array
    {
        try {
            $apiKey = config('services.deepseek.key');
            $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => 'Chưa cấu hình DEEPSEEK_API_KEY.',
                ];
            }

            $systemPrompt = <<<PROMPT
Bạn là AI phân tích học tập cho giáo viên trong hệ thống SmartLMS.
Hãy phân tích dữ liệu lớp/học sinh bằng tiếng Việt, ngắn gọn, thực tế, ưu tiên hành động.

Chỉ trả về JSON hợp lệ, không dùng markdown, không bọc ```json.
Schema:
{
  "summary": "Tóm tắt tình hình học tập",
  "risks": [
    {"level": "high|medium|low", "type": "score_drop|absence|quiz_missing|slow_progress|assignment_missing|other", "student": "Tên học sinh hoặc Toàn lớp", "reason": "Lý do cụ thể từ dữ liệu"}
  ],
  "actions": [
    {"priority": "high|medium|low", "student": "Tên học sinh hoặc Nhóm học sinh", "action": "Hành động giáo viên nên làm", "reason": "Vì sao nên làm"}
  ],
  "student_comments": [
    {"student": "Tên học sinh", "comment": "Nhận xét ngắn có thể dùng gửi phụ huynh/học sinh"}
  ]
}

Quy tắc:
- Không bịa dữ liệu ngoài payload.
- Nếu thiếu dữ liệu lịch sử để kết luận điểm giảm, hãy nói là chưa đủ dữ liệu thay vì khẳng định.
- Ưu tiên phát hiện: điểm giảm, vắng nhiều, không làm quiz, chậm tiến độ, thiếu bài tập.
- Đề xuất hành động cụ thể: nhắc nộp bài, giao bài bổ sung, ôn lại chủ đề/khóa học liên quan.
PROMPT;

            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->withoutVerifying()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => 'deepseek-v4-flash',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_UNICODE)],
                    ],
                    'temperature' => 0.2,
                ]);

            if ($response->failed()) {
                Log::warning('DeepSeek learning analysis failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'DeepSeek chưa phản hồi được. Vui lòng thử lại sau.',
                ];
            }

            $content = $response->json('choices.0.message.content');
            $analysis = $this->decodeJsonResponse($content);

            if (!$analysis) {
                return [
                    'success' => false,
                    'message' => 'AI trả về dữ liệu chưa đúng định dạng.',
                    'raw' => $content,
                ];
            }

            return [
                'success' => true,
                'analysis' => $analysis,
            ];
        } catch (\Exception $e) {
            Log::error('DeepSeek learning analysis error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Không thể kết nối đến AI phân tích học tập.',
            ];
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

    private function decodeJsonResponse(?string $content): ?array
    {
        if (!$content) {
            return null;
        }

        $cleaned = trim($content);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);

        $decoded = json_decode($cleaned, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $cleaned, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
