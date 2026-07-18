<?php

namespace Tests\Unit;

use App\Services\AiResponseValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AiResponseValidatorTest extends TestCase
{
    private AiResponseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AiResponseValidator;
    }

    public function test_it_accepts_and_normalizes_valid_quiz_questions(): void
    {
        $questions = $this->validator->quizQuestions([[
            'question' => ' 2 + 2 bằng bao nhiêu? ',
            'options' => [' 1 ', '2', '3', '4'],
            'correct_index' => 3,
            'explanation' => 'Phép cộng cơ bản.',
        ]], 1);

        $this->assertSame('2 + 2 bằng bao nhiêu?', $questions[0]['question']);
        $this->assertSame(['1', '2', '3', '4'], $questions[0]['options']);
    }

    #[DataProvider('invalidQuizProvider')]
    public function test_it_rejects_invalid_quiz_schema(array $questions): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->validator->quizQuestions($questions, 1);
    }

    public static function invalidQuizProvider(): array
    {
        return [
            'wrong option count' => [[['question' => 'Q', 'options' => ['A', 'B'], 'correct_index' => 0]]],
            'duplicate options' => [[['question' => 'Q', 'options' => ['A', 'A', 'B', 'C'], 'correct_index' => 0]]],
            'invalid correct index' => [[['question' => 'Q', 'options' => ['A', 'B', 'C', 'D'], 'correct_index' => 4]]],
        ];
    }

    public function test_it_rejects_assignment_score_that_does_not_match_rubric(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->validator->assignmentAnalysis([
            'suggested_score' => 8,
            'feedback' => 'Bài làm khá tốt.',
            'rubric_breakdown' => [
                ['criterion' => 'Nội dung', 'max_score' => 10, 'score' => 6],
            ],
        ], 10);
    }

    public function test_it_requires_exact_course_session_count(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->validator->coursePlan([
            'summary' => 'Kế hoạch',
            'modules' => [[
                'title' => 'Chương 1',
                'lessons' => [['title' => 'Bài 1']],
            ]],
        ], 2);
    }
}
