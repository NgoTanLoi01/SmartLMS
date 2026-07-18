<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AiPiiSanitizer;
use App\Services\AiResponseValidator;
use App\Services\DeepSeekService;
use App\Services\LocalCourseContextSearchService;
use App\Services\PersonalAssistantService;
use App\Services\VectorCourseContextSearchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class AiSecurityAndRagTest extends TestCase
{
    public function test_ai_routes_have_dedicated_rate_limits(): void
    {
        $this->assertContains('throttle:ai-chatbot', Route::getRoutes()->getByName('chatbot.send')->gatherMiddleware());

        foreach (['classes.ai-analysis', 'courses.ai-plan.generate', 'assignments.submissions.ai-analysis', 'ai.teaching-content.generate', 'quizzes.ai_generate.process', 'documents.store'] as $routeName) {
            $this->assertContains('throttle:ai-generation', Route::getRoutes()->getByName($routeName)->gatherMiddleware());
        }
    }

    public function test_chatbot_uses_vector_context_and_appends_citations(): void
    {
        config([
            'services.deepseek.key' => 'test-key',
            'services.deepseek.base_url' => 'https://deepseek.test',
            'services.deepseek.model' => 'test-model',
        ]);
        Http::fake([
            'https://deepseek.test/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'Câu trả lời dựa trên tài liệu [S1].']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ]),
        ]);

        $personal = Mockery::mock(PersonalAssistantService::class);
        $personal->shouldReceive('answer')->once()->andReturnNull();
        $local = Mockery::mock(LocalCourseContextSearchService::class);
        $local->shouldReceive('search')->once()->andReturn('');
        $vector = Mockery::mock(VectorCourseContextSearchService::class);
        $vector->shouldReceive('search')->once()->andReturn([
            'context' => '[S1] Tài liệu: giao-trinh.pdf\nNội dung liên quan',
            'sources' => [[
                'label' => 'S1',
                'document_name' => 'giao-trinh.pdf',
                'course_title' => 'Lập trình PHP',
                'pages' => [2],
            ], [
                'label' => 'S2',
                'document_name' => 'tai-lieu-khong-duoc-trich-dan.pdf',
                'course_title' => 'Lập trình PHP',
                'pages' => [8],
            ]],
        ]);

        $service = new DeepSeekService(
            $local,
            $personal,
            $vector,
            new AiResponseValidator,
            new AiPiiSanitizer,
        );
        $user = new User(['name' => 'Học viên', 'email' => 'student@example.com', 'role' => User::ROLE_STUDENT]);
        $user->id = 10;
        $answer = $service->sendMessage([
            ['role' => 'user', 'content' => 'Giải thích nội dung trong tài liệu'],
        ], $user);

        $this->assertStringContainsString('[S1]', $answer);
        $this->assertStringContainsString('giao-trinh.pdf', $answer);
        $this->assertStringContainsString('trang 2', $answer);
        $this->assertStringNotContainsString('tai-lieu-khong-duoc-trich-dan.pdf', $answer);
    }

    public function test_quiz_generation_rejects_invalid_ai_schema(): void
    {
        config([
            'services.deepseek.key' => 'test-key',
            'services.deepseek.base_url' => 'https://deepseek.test',
        ]);
        Http::fake([
            'https://deepseek.test/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'questions' => [[
                        'question' => 'Câu hỏi lỗi',
                        'options' => ['A', 'B'],
                        'correct_index' => 0,
                    ]],
                ])]]],
            ]),
        ]);

        $service = app(DeepSeekService::class);

        $this->expectException(\UnexpectedValueException::class);
        $service->generateQuizQuestions('Tạo một câu hỏi', 1);
    }
}
