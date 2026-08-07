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
            ['question_type' => 'essay', 'question' => 'Phân tích vai trò của semantic HTML', 'max_score' => 10, 'word_limit' => 500, 'allow_attachments' => false, 'rubric' => [['criterion' => 'Nội dung', 'max_score' => 7], ['criterion' => 'Trình bày', 'max_score' => 3]]],
            ['question_type' => 'code_debug', 'question' => 'Sửa lỗi CSS cho nút', 'max_score' => 10, 'starter_code' => '<style>.btn { color red; }</style><button class="btn">Lưu</button>', 'explanation_mode' => 'required', 'explanation_word_limit' => 150, 'rubric' => [['criterion' => 'Sửa mã', 'max_score' => 8], ['criterion' => 'Giải thích', 'max_score' => 2]]],
        ], 6);

        $this->assertSame([1, 3], $questions[0]['correct_indexes']);
        $this->assertFalse($questions[1]['statements'][1]['is_true']);
        $this->assertSame(['Hà Nội', 'Hanoi'], $questions[2]['blanks'][0]['accepted']);
        $this->assertSame(0.2, $questions[3]['numeric_tolerance']);
        $this->assertSame(10.0, $questions[4]['max_score']);
        $this->assertSame(500, $questions[4]['word_limit']);
        $this->assertSame('required', $questions[5]['explanation_mode']);
        $this->assertStringContainsString("<style>\n  .btn {\n    color red;", $questions[5]['starter_code']);
        $this->assertSame(10.0, array_sum(array_column($questions[5]['rubric'], 'max_score')));
    }

    public function test_it_rejects_manual_question_when_rubric_total_does_not_match_max_score(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->validator->quizQuestions([[
            'question_type' => 'essay',
            'question' => 'Trình bày kiến thức',
            'max_score' => 10,
            'word_limit' => 300,
            'rubric' => [['criterion' => 'Nội dung', 'max_score' => 8]],
        ]], 1);
    }

    public function test_it_rejects_javascript_in_html_css_debug_question(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->validator->quizQuestions([[
            'question_type' => 'code_debug',
            'question' => 'Sửa giao diện',
            'max_score' => 10,
            'starter_code' => '<script>alert(1)</script>',
            'explanation_mode' => 'required',
            'explanation_word_limit' => 100,
            'rubric' => [['criterion' => 'Sửa mã', 'max_score' => 10]],
        ]], 1);
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

    public function test_it_accepts_a_complete_practical_course_lesson(): void
    {
        $lesson = $this->practicalCourseLesson();

        $batch = $this->validator->coursePlanLessonBatch(['lessons' => [$lesson]], [$lesson['title']]);
        $plan = $this->validator->coursePlan([
            'summary' => 'Mỗi buổi tạo một sản phẩm để hoàn thiện bài tập lớn.',
            'modules' => [['title' => 'Chương thực hành', 'lessons' => $batch]],
        ], 1);

        $this->assertCount(2, $plan['modules'][0]['lessons'][0]['learning_outcomes']);
        $this->assertCount(3, $plan['modules'][0]['lessons'][0]['self_check_questions']);
        $this->assertSame('Phiếu khảo sát nhu cầu mạng', $plan['modules'][0]['lessons'][0]['deliverable']['name']);
    }

    public function test_it_rejects_a_course_lesson_with_a_short_real_world_scenario(): void
    {
        $lesson = $this->practicalCourseLesson();
        $lesson['real_world_scenario'] = 'Tình huống quá ngắn.';

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('80 đến 150 từ');

        $this->validator->coursePlanLessonBatch(['lessons' => [$lesson]], [$lesson['title']]);
    }

    public function test_it_rejects_a_course_lesson_without_a_word_and_powerpoint_update(): void
    {
        $lesson = $this->practicalCourseLesson();
        $lesson['capstone_update']['powerpoint'] = [];

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Nội dung PowerPoint');

        $this->validator->coursePlanLessonBatch(['lessons' => [$lesson]], [$lesson['title']]);
    }

    private function practicalCourseLesson(): array
    {
        return [
            'title' => 'Khảo sát nhu cầu mạng',
            'learning_outcomes' => [
                'Xác định đúng nhu cầu sử dụng mạng của khách hàng',
                'Lập và kiểm tra bảng khảo sát theo tiêu chí được giao',
            ],
            'real_world_scenario' => implode(' ', array_fill(0, 90, 'tình-huống-nghề-nghiệp')),
            'core_content' => [
                'explanations' => [
                    ['heading' => 'Thu thập nhu cầu', 'body' => implode(' ', array_fill(0, 80, 'giải-thích-thu-thập')), 'example' => 'Ví dụ khảo sát phòng máy của trường nghề.'],
                    ['heading' => 'Phân tích kết quả', 'body' => implode(' ', array_fill(0, 80, 'giải-thích-phân-tích')), 'example' => 'Ví dụ nhóm người dùng cần truy cập đồng thời.'],
                ],
                'comparison' => null,
                'process_steps' => ['Chuẩn bị câu hỏi', 'Phỏng vấn người dùng', 'Tổng hợp và kiểm tra dữ liệu'],
            ],
            'practice_task' => [
                'brief' => implode(' ', array_fill(0, 24, 'thực-hành-khảo-sát')),
                'steps' => ['Chọn đối tượng khảo sát', 'Thực hiện phỏng vấn', 'Hoàn thiện bảng kết quả'],
            ],
            'deliverable' => [
                'name' => 'Phiếu khảo sát nhu cầu mạng',
                'requirements' => ['Có ít nhất năm câu hỏi', 'Có kết luận và chữ ký nhóm'],
            ],
            'self_check_questions' => [
                'Cần hỏi những thông tin nào khi khảo sát?',
                'Làm sao kiểm tra dữ liệu khảo sát?',
                'Kết quả nào ảnh hưởng đến thiết kế mạng?',
            ],
            'capstone_update' => [
                'word_report' => ['Đưa bảng khảo sát và kết luận vào chương phân tích yêu cầu.'],
                'powerpoint' => ['Tạo một slide tóm tắt ba nhu cầu quan trọng nhất.'],
            ],
        ];
    }
}
