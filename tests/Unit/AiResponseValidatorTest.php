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

    public function test_it_normalizes_every_supported_mixed_question_type(): void
    {
        $questions = $this->validator->quizQuestions([
            ['question_type' => 'multiple_choice', 'question' => 'Chọn các số chẵn', 'options' => ['1', '2', '3', '4'], 'correct_indexes' => [1, 3]],
            ['question_type' => 'true_false_group', 'question' => 'Xác định đúng sai', 'statements' => [['text' => 'Mệnh đề A', 'is_true' => true], ['text' => 'Mệnh đề B', 'is_true' => false]]],
            ['question_type' => 'fill_blank', 'question' => 'Thủ đô là [[1]]', 'blanks' => [['accepted' => ['Hà Nội', 'Hanoi']]]],
            ['question_type' => 'numeric', 'question' => 'Kết quả phép tính', 'numeric_answer' => 10, 'numeric_tolerance' => 0.2, 'numeric_unit' => 'cm'],
        ], 4);

        $this->assertSame([1, 3], $questions[0]['correct_indexes']);
        $this->assertFalse($questions[1]['statements'][1]['is_true']);
        $this->assertSame(['Hà Nội', 'Hanoi'], $questions[2]['blanks'][0]['accepted']);
        $this->assertSame(0.2, $questions[3]['numeric_tolerance']);
    }

    public function test_it_normalizes_ai_quiz_distribution_draft(): void
    {
        $distribution = [];
        foreach (['single_choice', 'multiple_choice', 'true_false_group', 'fill_blank', 'numeric'] as $type) {
            $distribution[$type] = ['easy' => 0, 'medium' => 0, 'hard' => 0];
        }
        $distribution['fill_blank']['hard'] = 2;

        $draft = $this->validator->teachingDraft('quiz', [
            'title' => 'Đề AI',
            'time_limit' => 20,
            'topic' => 'HTML',
            'rationale' => 'Ưu tiên khả năng vận dụng.',
            'question_distribution' => $distribution,
        ]);

        $this->assertSame(2, $draft['question_distribution']['fill_blank']['hard']);
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
