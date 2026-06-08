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

    public function analyzeAssignmentSubmission(array $payload): array
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
Bạn là trợ lý AI hỗ trợ giáo viên chấm bài tự luận trong hệ thống SmartLMS.
Nhiệm vụ của bạn là đọc yêu cầu bài tập và bài làm học sinh, sau đó đề xuất điểm và nhận xét để giáo viên duyệt/chỉnh.
Nếu payload có grading_rubric, bắt buộc chấm theo rubric đó. Nếu grading_rubric trống, hãy tạo rubric tạm từ yêu cầu bài tập nhưng phải ghi rõ trong grading_notes rằng đề chưa có tiêu chí chấm cụ thể.

Chỉ trả về JSON hợp lệ, không dùng markdown, không bọc ```json.
Schema:
{
  "suggested_score": 8.0,
  "feedback": "Nhận xét ngắn gọn có thể gửi cho học sinh",
  "rubric_breakdown": [
    {"criterion": "Tên tiêu chí", "max_score": 4.0, "score": 3.0, "comment": "Lý do chấm tiêu chí này"}
  ],
  "strengths": ["Điểm làm tốt"],
  "improvements": ["Điểm cần cải thiện"],
  "grading_notes": "Ghi chú ngắn cho giáo viên về lý do đề xuất điểm"
}

Quy tắc:
- Chấm theo thang điểm assignment.grading_scale trong payload, mặc định là 10 nếu không có.
- Tổng điểm suggested_score phải bằng tổng score của rubric_breakdown, làm tròn 1 chữ số.
- Không cho điểm vượt quá max_score của từng tiêu chí.
- Không bịa nội dung ngoài yêu cầu và bài làm được cung cấp.
- Nếu bài làm quá ngắn, thiếu ý hoặc không liên quan, hãy giảm điểm và nêu rõ lý do.
- Nhận xét phải bằng tiếng Việt, cụ thể, lịch sự, có thể dùng trực tiếp cho học sinh.
- Ưu tiên nhận xét ngắn, bám tiêu chí; không viết lan man.
- Đây chỉ là gợi ý; giáo viên là người quyết định cuối cùng.
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
                    'temperature' => 0.15,
                ]);

            if ($response->failed()) {
                Log::warning('DeepSeek assignment grading failed', [
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

            $scale = max(1, (float) data_get($payload, 'assignment.grading_scale', 10));
            $score = isset($analysis['suggested_score']) ? (float) $analysis['suggested_score'] : null;
            if ($score !== null) {
                $analysis['suggested_score'] = max(0, min($scale, round($score, 1)));
            }

            $analysis['rubric_breakdown'] = collect($analysis['rubric_breakdown'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->map(function ($item) {
                    return [
                        'criterion' => (string) ($item['criterion'] ?? 'Tiêu chí'),
                        'max_score' => isset($item['max_score']) ? round((float) $item['max_score'], 1) : null,
                        'score' => isset($item['score']) ? round((float) $item['score'], 1) : null,
                        'comment' => (string) ($item['comment'] ?? ''),
                    ];
                })
                ->values()
                ->all();

            return [
                'success' => true,
                'analysis' => $analysis,
            ];
        } catch (\Exception $e) {
            Log::error('DeepSeek assignment grading error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Không thể kết nối đến AI hỗ trợ chấm bài.',
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
