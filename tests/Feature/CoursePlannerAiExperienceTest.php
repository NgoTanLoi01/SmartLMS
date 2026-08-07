<?php

namespace Tests\Feature;

use App\Jobs\GenerateCoursePlan;
use App\Models\AiOperation;
use App\Models\Course;
use App\Models\User;
use App\Services\DeepSeekService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoursePlannerAiExperienceTest extends TestCase
{
    public function test_course_plan_is_queued_instead_of_blocking_the_web_request(): void
    {
        $this->requireIsolatedSqliteDatabase();
        $this->createCoursePlannerSchema();
        Queue::fake();
        try {
            $teacher = User::factory()->create(['role' => User::ROLE_TEACHER, 'is_active' => true]);
            $course = Course::create([
                'title' => 'Mạng máy tính',
                'description' => 'Khóa học thực hành mạng căn bản.',
                'teacher_id' => $teacher->id,
            ]);

            $response = $this->actingAs($teacher)->postJson(route('courses.ai-plan.generate', $course), [
                'audience' => 'Học sinh trung cấp nghề lớp 11',
                'current_level' => 'Đã học kiến trúc máy tính',
                'learning_outcomes' => 'Có thể lắp ráp và kiểm tra một mạng LAN đơn giản.',
                'session_count' => 10,
                'minutes_per_session' => 180,
                'notes' => 'Ưu tiên thực hành.',
            ]);

            $response->assertAccepted()
                ->assertJsonPath('success', true)
                ->assertJsonPath('queued', true)
                ->assertJsonStructure(['operation_id', 'status_url', 'poll_interval_ms', 'poll_timeout_seconds']);

            $this->assertDatabaseHas('ai_operations', [
                'user_id' => $teacher->id,
                'feature' => 'course_plan',
                'subject_type' => Course::class,
                'subject_id' => $course->id,
                'status' => AiOperation::STATUS_QUEUED,
            ]);
            Queue::assertPushed(GenerateCoursePlan::class, fn (GenerateCoursePlan $job) => $job->payload['requirements']['session_count'] === 10
                && $job->payload['course']['title'] === 'Mạng máy tính'
                && $job->timeout > config('ai.course_plan.timeout_seconds')
            );
        } finally {
            foreach (['ai_operations', 'lessons', 'modules', 'courses', 'users'] as $table) {
                Schema::dropIfExists($table);
            }
        }
    }

    public function test_course_plan_request_is_compact_json_and_returns_usage(): void
    {
        config([
            'services.deepseek.key' => 'test-key',
            'services.deepseek.base_url' => 'https://deepseek.test/',
            'services.deepseek.model' => 'test-model',
            'ai.course_plan.outline_max_tokens' => 7000,
            'ai.course_plan.max_tokens' => 6500,
            'ai.course_plan.detail_batch_size' => 2,
            'ai.course_plan.request_attempts' => 1,
            'ai.course_plan.retry_delay_milliseconds' => 0,
        ]);
        Http::fakeSequence('https://deepseek.test/chat/completions')
            ->push([
                'choices' => [['message' => ['content' => json_encode([
                    'summary' => 'Kế hoạch thực hành trong hai buổi.',
                    'modules' => [[
                        'title' => 'Mạng LAN căn bản',
                        'lessons' => [
                            $this->outlineLesson('Nhận diện thiết bị mạng', 'Nhận diện đúng thiết bị', 'Bảng kiểm thiết bị'),
                            $this->outlineLesson('Thiết lập mạng LAN', 'Cấu hình được mạng LAN', 'Sơ đồ và ảnh kiểm tra mạng'),
                        ],
                    ]],
                ], JSON_UNESCAPED_UNICODE)]]],
                'usage' => ['prompt_tokens' => 40, 'completion_tokens' => 60, 'total_tokens' => 100],
            ])
            ->push([
                'choices' => [['message' => ['content' => json_encode([
                    'lessons' => [
                        $this->lesson('Nhận diện thiết bị mạng', 'thietbi'),
                        $this->lesson('Thiết lập mạng LAN', 'cauhinh'),
                    ],
                ], JSON_UNESCAPED_UNICODE)]]],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 480, 'total_tokens' => 600],
            ]);

        $result = app(DeepSeekService::class)->generateCoursePlan($this->coursePlanPayload(2));

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['plan']['modules'][0]['lessons']);
        $this->assertSame(700, $result['_usage']['total_tokens']);
        $this->assertStringContainsString('<h3>Kết quả cần đạt</h3>', $result['plan']['modules'][0]['lessons'][0]['content']);
        $this->assertStringContainsString('<h3>Tình huống thực tế</h3>', $result['plan']['modules'][0]['lessons'][0]['content']);
        $this->assertStringContainsString('<h3>Nội dung cốt lõi</h3>', $result['plan']['modules'][0]['lessons'][0]['content']);
        $this->assertStringContainsString('<h3>Nhiệm vụ thực hành</h3>', $result['plan']['modules'][0]['lessons'][0]['content']);
        $this->assertStringContainsString('<h3>Sản phẩm cần hoàn thành</h3>', $result['plan']['modules'][0]['lessons'][0]['content']);
        $this->assertStringContainsString('<h3>Tự kiểm tra</h3>', $result['plan']['modules'][0]['lessons'][0]['content']);
        $this->assertStringContainsString('<h3>Cập nhật bài tập lớn</h3>', $result['plan']['modules'][0]['lessons'][0]['content']);
        $this->assertStringContainsString('<table', $result['plan']['modules'][0]['lessons'][0]['content']);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === 'https://deepseek.test/chat/completions'
            && $request['model'] === 'test-model'
            && in_array($request['max_tokens'], [7000, 6500], true)
            && $request['response_format'] === ['type' => 'json_object']
            && $request['thinking'] === ['type' => 'disabled']);
    }

    public function test_course_plan_resumes_from_saved_lesson_checkpoint(): void
    {
        config([
            'services.deepseek.key' => 'test-key',
            'services.deepseek.base_url' => 'https://deepseek.test',
            'ai.course_plan.detail_batch_size' => 1,
            'ai.course_plan.request_attempts' => 1,
            'ai.course_plan.detail_validation_attempts' => 1,
        ]);
        $outline = [
            'summary' => 'Kế hoạch thực hành trong hai buổi.',
            'modules' => [[
                'title' => 'Mạng LAN căn bản',
                'lessons' => [
                    $this->outlineLesson('Nhận diện thiết bị mạng', 'Nhận diện đúng thiết bị', 'Bảng kiểm thiết bị'),
                    $this->outlineLesson('Thiết lập mạng LAN', 'Cấu hình được mạng LAN', 'Sơ đồ và ảnh kiểm tra mạng'),
                ],
            ]],
        ];
        Http::fake(['*' => Http::response($this->deepSeekResponse([
            'lessons' => [$this->lesson('Thiết lập mạng LAN', 'cauhinh')],
        ]))]);
        $progress = [];

        $result = app(DeepSeekService::class)->generateCoursePlan(
            $this->coursePlanPayload(2),
            [
                'outline' => $outline,
                'details' => ['0:0' => $this->lesson('Nhận diện thiết bị mạng', 'thietbi')],
                'usage' => ['prompt_tokens' => 40, 'completion_tokens' => 60, 'total_tokens' => 100],
            ],
            function (array $checkpoint, array $status) use (&$progress): void {
                $progress = compact('checkpoint', 'status');
            },
        );

        $this->assertTrue($result['success']);
        $this->assertSame(130, $result['_usage']['total_tokens']);
        $this->assertSame(2, $progress['status']['completed_lessons']);
        $this->assertArrayHasKey('0:0', $progress['checkpoint']['details']);
        $this->assertArrayHasKey('0:1', $progress['checkpoint']['details']);
        Http::assertSentCount(1);
    }

    public function test_course_plan_reports_a_truncated_json_response_precisely(): void
    {
        config([
            'services.deepseek.key' => 'test-key',
            'services.deepseek.base_url' => 'https://deepseek.test',
            'ai.course_plan.request_attempts' => 1,
            'ai.course_plan.retry_delay_milliseconds' => 0,
        ]);
        Http::fake(['*' => Http::response([
            'choices' => [[
                'message' => ['content' => '{"summary":"Khung khóa học","modules":['],
                'finish_reason' => 'length',
            ]],
            'usage' => ['completion_tokens' => 7000],
        ])]);

        $result = app(DeepSeekService::class)->generateCoursePlan($this->coursePlanPayload(10));

        $this->assertFalse($result['success']);
        $this->assertSame('AI_RESPONSE_TRUNCATED', $result['error_code']);
        $this->assertTrue($result['retryable']);
        $this->assertStringContainsString('chưa hoàn tất dữ liệu', $result['message']);
    }

    public function test_course_plan_timeout_returns_a_retryable_actionable_error(): void
    {
        config([
            'services.deepseek.key' => 'test-key',
            'services.deepseek.base_url' => 'https://deepseek.test',
            'ai.course_plan.request_attempts' => 2,
            'ai.course_plan.retry_delay_milliseconds' => 0,
        ]);
        Http::fake(fn () => throw new ConnectionException('Operation timed out'));

        $result = app(DeepSeekService::class)->generateCoursePlan($this->coursePlanPayload(2));

        $this->assertFalse($result['success']);
        $this->assertSame('AI_TIMEOUT', $result['error_code']);
        $this->assertFalse($result['retryable']);
        $this->assertStringContainsString('phản hồi quá thời gian', $result['message']);
        $this->assertStringContainsString('giảm số buổi', $result['message']);
    }

    public function test_course_plan_generates_detailed_lessons_in_small_batches(): void
    {
        config([
            'services.deepseek.key' => 'test-key',
            'services.deepseek.base_url' => 'https://deepseek.test',
            'ai.course_plan.detail_batch_size' => 2,
            'ai.course_plan.request_attempts' => 1,
            'ai.course_plan.retry_delay_milliseconds' => 0,
        ]);
        $titles = ['Khảo sát nhu cầu', 'Thiết kế sơ đồ', 'Cấu hình thiết bị', 'Kiểm tra hệ thống'];
        Http::fakeSequence('https://deepseek.test/chat/completions')
            ->push($this->deepSeekResponse([
                'summary' => 'Bốn bước hoàn thiện sản phẩm mạng.',
                'modules' => [[
                    'title' => 'Dự án mạng LAN',
                    'lessons' => collect($titles)->map(fn ($title, $index) => $this->outlineLesson($title, "Trọng tâm {$index}", "Sản phẩm {$index}"))->all(),
                ]],
            ]))
            ->push($this->deepSeekResponse(['lessons' => [
                $this->lesson($titles[0], 'khaosat'),
                $this->lesson($titles[1], 'sodo'),
            ]]))
            ->push($this->deepSeekResponse(['lessons' => [
                $this->lesson($titles[2], 'cauhinh'),
                $this->lesson($titles[3], 'kiemtra'),
            ]]));

        $result = app(DeepSeekService::class)->generateCoursePlan($this->coursePlanPayload(4));

        $this->assertTrue($result['success']);
        $this->assertCount(4, $result['plan']['modules'][0]['lessons']);
        Http::assertSentCount(3);
    }

    public function test_course_plan_provider_errors_are_classified_for_the_user(): void
    {
        config([
            'services.deepseek.key' => 'test-key',
            'services.deepseek.base_url' => 'https://deepseek.test',
            'ai.course_plan.request_attempts' => 2,
            'ai.course_plan.retry_delay_milliseconds' => 0,
        ]);
        Http::fake(['*' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        $result = app(DeepSeekService::class)->generateCoursePlan($this->coursePlanPayload(2));

        $this->assertSame('AI_RATE_LIMIT', $result['error_code']);
        $this->assertFalse($result['retryable']);
        $this->assertStringContainsString('giới hạn tần suất', $result['message']);
        Http::assertSentCount(2);
    }

    public function test_course_page_explains_background_processing_and_handles_polling_errors(): void
    {
        $view = file_get_contents(resource_path('views/courses/show.blade.php'));
        $scripts = file_get_contents(resource_path('views/courses/partials/show-page-scripts.blade.php'));

        $this->assertStringContainsString('Tác vụ chạy nền', $view);
        $this->assertStringContainsString('waitForCoursePlan', $scripts);
        $this->assertStringContainsString("status === 'failed'", $scripts);
        $this->assertStringContainsString('Mất kết nối tới máy chủ', $scripts);
        $this->assertStringContainsString('từng nhóm bài', $scripts);
        $this->assertStringContainsString('Đã hoàn thành và lưu', $scripts);
    }

    private function coursePlanPayload(int $sessionCount): array
    {
        return [
            'course' => [
                'title' => 'Mạng máy tính',
                'description' => 'Khóa học thực hành.',
                'existing_modules' => [],
            ],
            'requirements' => [
                'audience' => 'Học sinh trung cấp nghề',
                'current_level' => 'Mới học mạng máy tính',
                'learning_outcomes' => 'Thiết lập được mạng LAN.',
                'session_count' => $sessionCount,
                'minutes_per_session' => 180,
                'notes' => 'Ưu tiên thực hành.',
            ],
        ];
    }

    private function outlineLesson(string $title, string $focus, string $product): array
    {
        return ['title' => $title, 'focus' => $focus, 'capstone_product' => $product];
    }

    private function deepSeekResponse(array $content): array
    {
        return [
            'choices' => [['message' => ['content' => json_encode($content, JSON_UNESCAPED_UNICODE)]]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
        ];
    }

    private function lesson(string $title, string $keyword): array
    {
        $scenario = implode(' ', array_fill(0, 90, "tình-huống-{$keyword}"));
        $bodyOne = implode(' ', array_fill(0, 80, "giải-thích-{$keyword}-một"));
        $bodyTwo = implode(' ', array_fill(0, 80, "giải-thích-{$keyword}-hai"));
        $practiceBrief = implode(' ', array_fill(0, 24, "thực-hành-{$keyword}"));

        return [
            'title' => $title,
            'learning_outcomes' => [
                "Xác định đúng thành phần {$keyword} trong tình huống được giao",
                "Thực hiện và kiểm tra quy trình {$keyword} theo bảng tiêu chí",
            ],
            'real_world_scenario' => $scenario,
            'core_content' => [
                'explanations' => [
                    ['heading' => "Khái niệm {$keyword}", 'body' => $bodyOne, 'example' => "Ví dụ {$keyword} tại phòng máy"],
                    ['heading' => "Cách áp dụng {$keyword}", 'body' => $bodyTwo, 'example' => "Tình huống áp dụng {$keyword}"],
                ],
                'comparison' => [
                    'headers' => ['Tiêu chí', 'Phương án A', 'Phương án B'],
                    'rows' => [['Chi phí', 'Thấp', 'Cao'], ['Khả năng mở rộng', 'Hạn chế', 'Tốt']],
                ],
                'process_steps' => ['Chuẩn bị dữ liệu và dụng cụ', 'Thực hiện đúng thứ tự', 'Kiểm tra và ghi nhận kết quả'],
            ],
            'practice_task' => [
                'brief' => $practiceBrief,
                'steps' => ['Đọc yêu cầu thực hành', 'Thực hiện trên thiết bị hoặc phần mềm', 'Chụp và ghi lại kết quả'],
            ],
            'deliverable' => [
                'name' => "Phiếu kết quả {$keyword}",
                'requirements' => ['Có đầy đủ thông tin nhóm', 'Có minh chứng và kết luận'],
            ],
            'self_check_questions' => [
                "Khi nào cần sử dụng {$keyword}?",
                "Các bước kiểm tra {$keyword} gồm những gì?",
                "Lỗi nào thường gặp khi thực hiện {$keyword}?",
            ],
            'capstone_update' => [
                'word_report' => ["Thêm phiếu kết quả {$keyword} và phần giải thích vào báo cáo Word"],
                'powerpoint' => ["Thêm một slide minh chứng {$keyword} vào PowerPoint"],
            ],
        ];
    }

    private function createCoursePlannerSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->boolean('is_active')->nullable()->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('teacher_id');
            $table->timestamps();
        });
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->unsignedInteger('order')->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->string('title');
            $table->unsignedInteger('order')->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('feature', 80);
            $table->string('provider', 40)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('status', 20);
            $table->nullableMorphs('subject');
            $table->json('metadata')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('estimated_cost_usd', 12, 8)->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }
}
