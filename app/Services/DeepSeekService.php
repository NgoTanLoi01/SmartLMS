<?php

namespace App\Services;

use App\Models\AiOperation;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    public function __construct(private LocalCourseContextSearchService $contextSearch) {}

    public function sendMessage(array $messages, ?User $user = null, array $options = []): string
    {
        try {
            $lastUserMessage = (string) (end($messages)['content'] ?? '');
            $lessonContext = '';

            if (! empty($options['lesson_id'])) {
                $lessonContext = $this->contextSearch->lessonContext((int) $options['lesson_id'], $user);
            }

            $searchContext = $this->contextSearch->search($lastUserMessage, $user);
            $context = trim(implode("\n\n---\n\n", array_filter([$lessonContext, $searchContext])));

            return $this->askDeepSeek($messages, $context, $options);
        } catch (\Exception $e) {
            Log::error('Lỗi quy trình Chatbot: '.$e->getMessage());

            return 'Không thể kết nối đến máy chủ AI.';
        }
    }

    public function generateQuizQuestions(string $prompt): array
    {
        $response = Http::withToken(config('services.deepseek.key'))
            ->timeout(120)
            ->withoutVerifying()
            ->post(rtrim(config('services.deepseek.base_url', 'https://api.deepseek.com'), '/').'/v1/chat/completions', [
                'model' => config('services.deepseek.model', 'deepseek-v4-flash'),
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a professional teacher assistant. Support language: Vietnamese. Always return valid JSON only.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);
        if ($response->failed()) {
            throw new \RuntimeException('DeepSeek tạo câu hỏi lỗi HTTP '.$response->status());
        }
        $decoded = $this->decodeJsonResponse($response->json('choices.0.message.content'));
        $questions = $decoded['questions'] ?? $decoded['data'] ?? $decoded;
        if (! is_array($questions)) {
            throw new \RuntimeException('AI trả về danh sách câu hỏi không hợp lệ.');
        }

        return ['questions' => $questions, 'usage' => $response->json('usage') ?? []];
    }

    public function analyzeLearning(array $payload): array
    {
        try {
            $apiKey = config('services.deepseek.key');
            $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

            if (! $apiKey) {
                return [
                    'success' => false,
                    'message' => 'Chưa cấu hình DEEPSEEK_API_KEY.',
                ];
            }

            $systemPrompt = <<<'PROMPT'
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

            $startedAt = hrtime(true);
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->withoutVerifying()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => config('services.deepseek.model', 'deepseek-v4-flash'),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_UNICODE)],
                    ],
                    'temperature' => 0.2,
                ]);
            $this->trackSynchronousResponse('learning_analysis', $response, $startedAt);

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

            if (! $analysis) {
                return [
                    'success' => false,
                    'message' => 'AI trả về dữ liệu chưa đúng định dạng.',
                    'raw' => $content,
                ];
            }

            return [
                'success' => true,
                'analysis' => $analysis,
                '_usage' => $response->json('usage') ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('DeepSeek learning analysis error: '.$e->getMessage());

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

            if (! $apiKey) {
                return [
                    'success' => false,
                    'message' => 'Chưa cấu hình DEEPSEEK_API_KEY.',
                ];
            }

            $systemPrompt = <<<'PROMPT'
Bạn là trợ lý AI hỗ trợ giáo viên chấm bài tự luận trong hệ thống SmartLMS.
Nhiệm vụ của bạn là đọc yêu cầu bài tập và bài làm học sinh, sau đó đề xuất điểm và nhận xét để giáo viên duyệt/chỉnh.
Bài làm có thể gồm nội dung tự luận học sinh nhập trực tiếp và/hoặc văn bản được hệ thống trích xuất từ file PDF, DOCX, TXT, HTML, CSS, JS, PHP, MD.
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
  "review_flags": [
    {"type": "off_topic|too_short|possible_copy|missing_rubric|needs_manual_review|other", "level": "high|medium|low", "message": "Cảnh báo ngắn cho giáo viên"}
  ],
  "grading_notes": "Ghi chú ngắn cho giáo viên về lý do đề xuất điểm"
}

Quy tắc:
- Chấm theo thang điểm assignment.grading_scale trong payload, mặc định là 10 nếu không có.
- Tổng điểm suggested_score phải bằng tổng score của rubric_breakdown, làm tròn 1 chữ số.
- Không cho điểm vượt quá max_score của từng tiêu chí.
- Không bịa nội dung ngoài yêu cầu và bài làm được cung cấp.
- Nếu bài làm quá ngắn, thiếu ý hoặc không liên quan, hãy giảm điểm và nêu rõ lý do.
- Nếu bài làm có dấu hiệu lạc đề, quá ngắn, trả lời chung chung, sao chép mẫu, hoặc cần giáo viên xem thủ công, hãy thêm vào review_flags.
- Nếu payload.submission.file_text_extracted là true, hãy nêu trong grading_notes rằng AI đã phân tích nội dung trích xuất từ file; nếu nội dung có vẻ thiếu/mất định dạng, thêm review_flags needs_manual_review.
- Không khẳng định chắc chắn đạo văn/sao chép nếu không có bằng chứng; chỉ ghi "có dấu hiệu" hoặc "cần kiểm tra thêm".
- Nhận xét phải bằng tiếng Việt, cụ thể, lịch sự, có thể dùng trực tiếp cho học sinh.
- Ưu tiên nhận xét ngắn, bám tiêu chí; không viết lan man.
- Đây chỉ là gợi ý; giáo viên là người quyết định cuối cùng.
PROMPT;

            $startedAt = hrtime(true);
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->withoutVerifying()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => 'deepseek-v4-flash',
                    'messages' => [
                        ['role' => 'system', 'content' => $this->cleanUtf8($systemPrompt)],
                        ['role' => 'user', 'content' => $this->cleanUtf8(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) ?: '{}')],
                    ],
                    'temperature' => 0.15,
                ]);
            $this->trackSynchronousResponse('assignment_grading', $response, $startedAt);

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

            if (! $analysis) {
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

            $analysis['strengths'] = collect($analysis['strengths'] ?? [])
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->map(fn ($item) => trim($item))
                ->values()
                ->all();

            $analysis['improvements'] = collect($analysis['improvements'] ?? [])
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->map(fn ($item) => trim($item))
                ->values()
                ->all();

            $analysis['review_flags'] = collect($analysis['review_flags'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->map(function ($item) {
                    return [
                        'type' => (string) ($item['type'] ?? 'other'),
                        'level' => in_array(($item['level'] ?? ''), ['high', 'medium', 'low'], true) ? $item['level'] : 'medium',
                        'message' => (string) ($item['message'] ?? ''),
                    ];
                })
                ->filter(fn ($item) => trim($item['message']) !== '')
                ->values()
                ->all();

            return [
                'success' => true,
                'analysis' => $analysis,
                '_usage' => $response->json('usage') ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('DeepSeek assignment grading error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Không thể kết nối đến AI hỗ trợ chấm bài.',
            ];
        }
    }

    public function generateTeachingContent(string $type, array $payload, ?User $user = null): array
    {
        try {
            $apiKey = config('services.deepseek.key');
            $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

            if (! $apiKey) {
                return [
                    'success' => false,
                    'message' => 'Chưa cấu hình DEEPSEEK_API_KEY.',
                ];
            }

            $lessonContext = '';
            if (! empty($payload['lesson_id'])) {
                $lessonContext = $this->contextSearch->lessonContext((int) $payload['lesson_id'], $user);
            }

            if ($lessonContext === '' && ! empty($payload['module_id'])) {
                $lessonContext = $this->contextSearch->moduleContext((int) $payload['module_id'], $user);
            }

            $sourceText = $this->cleanUtf8((string) ($payload['source_text'] ?? ''));
            $context = trim(implode("\n\n---\n\n", array_filter([$lessonContext, $sourceText])));

            if ($context === '') {
                return [
                    'success' => false,
                    'message' => 'Chưa có đủ nội dung bài học để AI soạn bản nháp.',
                ];
            }

            $schema = match ($type) {
                'assignment' => <<<'PROMPT'
{
  "title": "Tên bài tập ngắn gọn",
  "type": "essay|file|mixed",
  "instructions": "Yêu cầu bài tập rõ ràng, có các bước thực hiện",
  "grading_scale": 10,
  "grading_rubric": "Tiêu chí chấm điểm theo từng dòng"
}
PROMPT,
                'rubric' => <<<'PROMPT'
{
  "grading_scale": 10,
  "grading_rubric": "Tiêu chí chấm điểm theo từng dòng, có điểm tối đa cho từng tiêu chí"
}
PROMPT,
                'quiz' => <<<'PROMPT'
{
  "title": "Tên quiz ngắn gọn",
  "time_limit": 20,
  "easy_count": 5,
  "medium_count": 5,
  "hard_count": 2,
  "topic": "Chủ đề dùng để sinh câu hỏi trong ngân hàng"
}
PROMPT,
                'lesson_summary' => <<<'PROMPT'
{
  "title": "Tiêu đề bài học nếu cần chỉnh lại",
  "content": "<p>Nội dung bài học tóm tắt, rõ ý, có thể dùng trong trình soạn thảo</p>"
}
PROMPT,
                default => null,
            };

            if (! $schema) {
                return [
                    'success' => false,
                    'message' => 'Loại nội dung AI chưa được hỗ trợ.',
                ];
            }

            $systemPrompt = <<<PROMPT
Bạn là trợ lý AI hỗ trợ giáo viên soạn nội dung trong SmartLMS.
Hãy tạo bản nháp thực tế, ngắn gọn, dễ chỉnh sửa, bằng tiếng Việt.
Chỉ trả về JSON hợp lệ, không markdown, không bọc ```json.

Schema bắt buộc:
{$schema}

Quy tắc:
- Bám sát nội dung bài học/tài liệu được cung cấp, không bịa kiến thức ngoài phạm vi.
- Giáo viên sẽ duyệt trước khi lưu, vì vậy hãy viết ở dạng bản nháp có thể chỉnh sửa.
- Tránh quá dài; ưu tiên rõ việc học sinh cần làm, sản phẩm cần nộp và tiêu chí đánh giá.
- Với rubric, tổng điểm nên khớp grading_scale.
- Với lesson_summary, content có thể dùng HTML đơn giản: p, ul, ol, li, strong.
PROMPT;

            $userPayload = json_encode([
                'type' => $type,
                'teacher_request' => $payload['teacher_request'] ?? '',
                'current_title' => $payload['current_title'] ?? '',
                'current_instructions' => $payload['current_instructions'] ?? '',
                'context' => $context,
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);

            $startedAt = hrtime(true);
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->withoutVerifying()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => 'deepseek-v4-flash',
                    'messages' => [
                        ['role' => 'system', 'content' => $this->cleanUtf8($systemPrompt)],
                        ['role' => 'user', 'content' => $this->cleanUtf8($userPayload ?: '{}')],
                    ],
                    'temperature' => 0.25,
                ]);
            $this->trackSynchronousResponse('teaching_content', $response, $startedAt);

            if ($response->failed()) {
                Log::warning('DeepSeek teaching content failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'DeepSeek chưa soạn được nội dung. Vui lòng thử lại.',
                ];
            }

            $content = $response->json('choices.0.message.content');
            $draft = $this->decodeJsonResponse($content);

            if (! $draft) {
                return [
                    'success' => false,
                    'message' => 'AI trả về bản nháp chưa đúng định dạng.',
                    'raw' => $content,
                ];
            }

            return [
                'success' => true,
                'draft' => $draft,
            ];
        } catch (\Exception $e) {
            Log::error('DeepSeek teaching content error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Không thể kết nối đến AI soạn nội dung.',
            ];
        }
    }

    public function generateCoursePlan(array $payload): array
    {
        try {
            $apiKey = config('services.deepseek.key');
            $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

            if (! $apiKey) {
                return ['success' => false, 'message' => 'Chưa cấu hình DEEPSEEK_API_KEY.'];
            }

            $systemPrompt = <<<'PROMPT'
Bạn là chuyên gia thiết kế chương trình giảng dạy cho giáo viên trong SmartLMS.
Hãy tạo một kế hoạch khóa học thực tế bằng tiếng Việt, phù hợp đúng đối tượng, trình độ, số buổi và thời lượng được cung cấp.
Nếu khóa học đã có nội dung, tránh lặp lại nguyên văn các chương/bài hiện có.

Chỉ trả về JSON hợp lệ, không markdown, không bọc ```json. Schema:
{
  "summary": "Mô tả ngắn định hướng kế hoạch",
  "modules": [
    {
      "title": "Tên chương",
      "lessons": [
        {
          "title": "Tên bài học/buổi học",
          "objectives": ["Mục tiêu đo lường được"],
          "key_topics": ["Kiến thức trọng tâm"],
          "activities": ["Hoạt động trên lớp kèm thời lượng gợi ý"],
          "assessment": "Cách kiểm tra nhanh cuối buổi",
          "assignment": "Bài tập phù hợp hoặc Không có"
        }
      ]
    }
  ]
}

Quy tắc:
- Tổng số lesson phải đúng bằng session_count; mỗi lesson tương ứng một buổi học.
- Phân bổ lesson vào các chương hợp lý, không tạo chương rỗng.
- Nội dung phải đủ cụ thể để giáo viên có thể dùng làm bản nháp giảng dạy.
- Hoạt động phải phù hợp minutes_per_session và có thực hành khi phù hợp.
- Bài tập bám sát mục tiêu buổi học, không quá sức đối tượng.
- Không bịa yêu cầu đầu ra ngoài dữ liệu người dùng cung cấp.
PROMPT;

            $startedAt = hrtime(true);
            $response = Http::withToken($apiKey)
                ->timeout(120)
                ->withoutVerifying()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => 'deepseek-v4-flash',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $this->cleanUtf8(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE) ?: '{}')],
                    ],
                    'temperature' => 0.35,
                ]);
            $this->trackSynchronousResponse('course_plan', $response, $startedAt);

            if ($response->failed()) {
                Log::warning('DeepSeek course plan failed', ['status' => $response->status(), 'body' => $response->body()]);

                return ['success' => false, 'message' => 'AI chưa tạo được kế hoạch. Vui lòng thử lại.'];
            }

            $plan = $this->decodeJsonResponse($response->json('choices.0.message.content'));
            if (! $plan || empty($plan['modules']) || ! is_array($plan['modules'])) {
                return ['success' => false, 'message' => 'AI trả về kế hoạch chưa đúng định dạng. Vui lòng tạo lại.'];
            }

            $modules = collect($plan['modules'])->filter(fn ($module) => is_array($module))
                ->map(function ($module) {
                    $lessons = collect($module['lessons'] ?? [])->filter(fn ($lesson) => is_array($lesson))
                        ->map(function ($lesson) {
                            $section = function (string $heading, $value): string {
                                $items = is_array($value) ? $value : array_filter([(string) $value]);
                                if (! $items) {
                                    return '';
                                }
                                $list = collect($items)->map(fn ($item) => '<li>'.e((string) $item).'</li>')->implode('');

                                return '<h3>'.e($heading).'</h3><ul>'.$list.'</ul>';
                            };

                            $content = $section('Mục tiêu buổi học', $lesson['objectives'] ?? [])
                                .$section('Kiến thức trọng tâm', $lesson['key_topics'] ?? [])
                                .$section('Hoạt động trên lớp', $lesson['activities'] ?? [])
                                .$section('Kiểm tra cuối buổi', $lesson['assessment'] ?? '')
                                .$section('Bài tập gợi ý', $lesson['assignment'] ?? '');

                            return ['title' => trim((string) ($lesson['title'] ?? 'Bài học')), 'content' => $content];
                        })->filter(fn ($lesson) => $lesson['title'] !== '')->values()->all();

                    return ['title' => trim((string) ($module['title'] ?? 'Chương học')), 'lessons' => $lessons];
                })->filter(fn ($module) => $module['title'] !== '' && count($module['lessons']) > 0)->values()->all();

            if (! $modules) {
                return ['success' => false, 'message' => 'Kế hoạch AI chưa có chương hoặc bài học hợp lệ.'];
            }

            return ['success' => true, 'plan' => ['summary' => (string) ($plan['summary'] ?? ''), 'modules' => $modules]];
        } catch (\Throwable $e) {
            Log::error('DeepSeek course plan error: '.$e->getMessage());

            return ['success' => false, 'message' => 'Không thể kết nối đến AI thiết kế khóa học.'];
        }
    }

    private function askDeepSeek(array $historyMessages, string $context, array $options = []): string
    {
        $apiKey = config('services.deepseek.key');
        $baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');

        $assistMode = (string) ($options['assist_mode'] ?? '');

        $systemContent = "Bạn là trợ giảng AI học tập của hệ thống SmartLMS. Hãy trả lời bằng tiếng Việt, rõ ràng, thân thiện và ưu tiên nội dung trong khóa học hoặc bài học hiện tại của người dùng.\n";
        $systemContent .= "Khi đang có ngữ cảnh bài học hiện tại, hãy bám vào bài đó trước; chỉ mở rộng sang nội dung liên quan nếu thật sự cần.\n";
        $systemContent .= "Nếu học sinh hỏi chưa hiểu, hãy giải thích lại từng bước, dùng ví dụ ngắn, tránh trả lời quá dài.\n";
        $systemContent .= "Nếu được yêu cầu tóm tắt, hãy tóm tắt theo ý chính và gợi ý 2-3 điểm cần nhớ.\n";
        $systemContent .= "Nếu được yêu cầu ôn tập, hãy đưa ra câu hỏi tự kiểm tra hoặc việc nên xem lại, không tạo cảm giác quá tải.\n";

        if ($assistMode !== '') {
            $systemContent .= "Chế độ hỗ trợ hiện tại: {$assistMode}.\n";
        }

        if (! empty($context)) {
            $systemContent .= "Dữ liệu tìm thấy từ bài học và file bài giảng trong SmartLMS:\n".$context."\n\n";
            $systemContent .= 'Quy tắc: chỉ dùng dữ liệu trên làm nguồn chính; nếu cần suy luận thêm, hãy nói rõ đó là phần giải thích thêm.';
        } else {
            $systemContent .= 'Hiện không tìm thấy nội dung liên quan trong khóa học/bài giảng của người dùng. Nếu câu hỏi cần dữ liệu khóa học, hãy nói rằng chưa tìm thấy tài liệu phù hợp và gợi ý người dùng hỏi rõ hơn hoặc kiểm tra bài học liên quan.';
        }

        // Tạo danh sách tin nhắn cho API DeepSeek
        $finalMessages = [['role' => 'system', 'content' => $this->cleanUtf8($systemContent)]];

        // Chuyển đổi role 'assistant' (nếu có từ JS) thành 'assistant' chuẩn API
        foreach ($historyMessages as $msg) {
            $finalMessages[] = [
                'role' => $msg['role'] === 'assistant' ? 'assistant' : $msg['role'],
                'content' => $this->cleanUtf8((string) ($msg['content'] ?? '')),
            ];
        }

        $startedAt = hrtime(true);
        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->withoutVerifying()
            ->post("{$baseUrl}/chat/completions", [
                'model' => 'deepseek-v4-flash',
                'messages' => $finalMessages,
                'temperature' => 0.3,
            ]);
        $this->trackSynchronousResponse('chatbot', $response, $startedAt);

        return $response->successful() ? $response->json('choices.0.message.content') : 'AI đang bận, thử lại sau nhé!';
    }

    private function decodeJsonResponse(?string $content): ?array
    {
        if (! $content) {
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

    private function trackSynchronousResponse(string $feature, $response, int $startedAt): void
    {
        // Queue jobs already own an AiOperation record and run in console.
        if (app()->runningInConsole()) {
            return;
        }

        $usage = $response->json('usage') ?? [];
        $operation = new AiOperation([
            'user_id' => auth()->id(),
            'feature' => $feature,
            'provider' => 'deepseek',
            'model' => config('services.deepseek.model', 'deepseek-v4-flash'),
            'status' => $response->successful() ? AiOperation::STATUS_COMPLETED : AiOperation::STATUS_FAILED,
            'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
            'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
            'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'completed_at' => $response->successful() ? now() : null,
            'failed_at' => $response->failed() ? now() : null,
            'error_message' => $response->failed() ? 'DeepSeek HTTP '.$response->status() : null,
        ]);
        $operation->estimated_cost_usd = $operation->estimatedCost($usage);
        $operation->save();
    }

    private function cleanUtf8(string $text): string
    {
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text) ?: '';
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
    }
}
