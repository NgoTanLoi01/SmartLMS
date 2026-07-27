<?php

namespace App\Services;

use App\Models\Question;

class AiResponseValidator
{
    public function quizQuestions(array $questions, int $expectedQuantity): array
    {
        if (! array_is_list($questions) || count($questions) !== $expectedQuantity) {
            throw new \UnexpectedValueException("AI phải trả về đúng {$expectedQuantity} câu hỏi.");
        }

        return collect($questions)->map(function ($question, int $index) {
            if (! is_array($question)) {
                throw new \UnexpectedValueException('Câu hỏi AI số '.($index + 1).' không đúng cấu trúc.');
            }

            $text = trim((string) ($question['question'] ?? ''));
            $type = (string) ($question['question_type'] ?? Question::TYPE_SINGLE_CHOICE);
            if ($text === '' || ! array_key_exists($type, Question::typeLabels())) {
                throw new \UnexpectedValueException('Câu hỏi AI thiếu nội dung hoặc có hình thức không được hỗ trợ.');
            }

            $normalized = [
                'question_type' => $type,
                'question' => $text,
                'explanation' => trim((string) ($question['explanation'] ?? '')),
                'quality_review' => collect($question['quality_review'] ?? [])->map(fn ($item) => trim((string) $item))->filter()->take(5)->values()->all(),
            ];

            return array_merge($normalized, match ($type) {
                Question::TYPE_SINGLE_CHOICE => $this->normalizeChoice($question, false),
                Question::TYPE_MULTIPLE_CHOICE => $this->normalizeChoice($question, true),
                Question::TYPE_TRUE_FALSE_GROUP => $this->normalizeTrueFalse($question),
                Question::TYPE_FILL_BLANK => $this->normalizeFillBlank($question, $text),
                Question::TYPE_NUMERIC => $this->normalizeNumeric($question),
            });
        })->all();
    }

    private function normalizeChoice(array $question, bool $multiple): array
    {
        $options = $question['options'] ?? null;
        if (! is_array($options) || count($options) !== 4) {
            throw new \UnexpectedValueException('Câu lựa chọn do AI sinh phải có đúng 4 phương án.');
        }
        $options = collect($options)->map(fn ($option) => trim((string) $option))->values()->all();
        if (collect($options)->contains('') || count(array_unique($options)) !== count($options)) {
            throw new \UnexpectedValueException('Các phương án AI phải đầy đủ và không trùng nhau.');
        }

        $indexes = $question['correct_indexes'] ?? (array_key_exists('correct_index', $question) ? [$question['correct_index']] : []);
        if (! is_array($indexes) || ($multiple ? count($indexes) < 2 : count($indexes) !== 1)) {
            throw new \UnexpectedValueException($multiple ? 'Câu nhiều đáp án phải có ít nhất hai đáp án đúng.' : 'Câu một đáp án phải có đúng một đáp án đúng.');
        }
        $indexes = array_map(fn ($index) => filter_var($index, FILTER_VALIDATE_INT), $indexes);
        if (in_array(false, $indexes, true) || count(array_unique($indexes)) !== count($indexes)) {
            throw new \UnexpectedValueException('Danh sách chỉ số đáp án đúng không hợp lệ.');
        }
        foreach ($indexes as $index) {
            if ($index < 0 || $index >= count($options)) {
                throw new \UnexpectedValueException('Chỉ số đáp án đúng nằm ngoài danh sách phương án.');
            }
        }
        if ($multiple && count($indexes) >= count($options)) {
            throw new \UnexpectedValueException('Câu nhiều đáp án phải có ít nhất một phương án nhiễu.');
        }

        return [
            'options' => $options,
            'correct_indexes' => array_values($indexes),
            'correct_index' => $multiple ? null : $indexes[0],
        ];
    }

    private function normalizeTrueFalse(array $question): array
    {
        $statements = $question['statements'] ?? null;
        if (! is_array($statements) || count($statements) < 2 || count($statements) > 10) {
            throw new \UnexpectedValueException('Câu Đúng/Sai phải có từ 2 đến 10 nhận định.');
        }

        $normalized = collect($statements)->map(function ($statement) {
            if (! is_array($statement)) {
                throw new \UnexpectedValueException('Nhận định Đúng/Sai không đúng cấu trúc.');
            }
            $text = trim((string) ($statement['text'] ?? ''));
            $value = $statement['is_true'] ?? $statement['value'] ?? null;
            if ($text === '' || ! in_array($value, [true, false, 1, 0, '1', '0'], true)) {
                throw new \UnexpectedValueException('Mỗi nhận định cần có nội dung và giá trị Đúng/Sai.');
            }

            return ['text' => $text, 'is_true' => filter_var($value, FILTER_VALIDATE_BOOLEAN)];
        })->values()->all();

        if (collect($normalized)->pluck('text')->unique()->count() !== count($normalized)) {
            throw new \UnexpectedValueException('Các nhận định Đúng/Sai không được trùng nhau.');
        }

        return ['statements' => $normalized];
    }

    private function normalizeFillBlank(array $question, string $text): array
    {
        $blanks = $question['blanks'] ?? null;
        preg_match_all('/\[\[\s*\d+\s*\]\]/u', $text, $matches);
        if (! is_array($blanks) || count($blanks) < 1 || count($blanks) > 10 || count($matches[0]) !== count($blanks)) {
            throw new \UnexpectedValueException('Số ô trống trong nội dung phải khớp danh sách đáp án.');
        }

        $normalized = collect($blanks)->map(function ($blank) {
            $accepted = is_array($blank) ? ($blank['accepted'] ?? $blank) : [$blank];
            $accepted = collect($accepted)->map(fn ($answer) => trim((string) $answer))->filter()->unique()->values()->all();
            if ($accepted === []) {
                throw new \UnexpectedValueException('Mỗi ô trống phải có ít nhất một đáp án được chấp nhận.');
            }

            return ['accepted' => $accepted];
        })->all();

        return ['blanks' => $normalized, 'case_sensitive' => (bool) ($question['case_sensitive'] ?? false)];
    }

    private function normalizeNumeric(array $question): array
    {
        $answer = $question['numeric_answer'] ?? $question['target'] ?? null;
        $tolerance = $question['numeric_tolerance'] ?? $question['tolerance'] ?? 0;
        if (! is_numeric($answer) || ! is_numeric($tolerance) || (float) $tolerance < 0) {
            throw new \UnexpectedValueException('Câu trả lời số cần đáp án và sai số hợp lệ.');
        }

        return [
            'numeric_answer' => (float) $answer,
            'numeric_tolerance' => (float) $tolerance,
            'numeric_unit' => mb_substr(trim((string) ($question['numeric_unit'] ?? $question['unit'] ?? '')), 0, 50),
        ];
    }

    public function assignmentAnalysis(array $analysis, float $scale): array
    {
        $score = $analysis['suggested_score'] ?? null;
        $breakdown = $analysis['rubric_breakdown'] ?? null;
        if (! is_numeric($score) || (float) $score < 0 || (float) $score > $scale || ! is_array($breakdown) || $breakdown === []) {
            throw new \UnexpectedValueException('Kết quả chấm AI thiếu điểm hoặc rubric hợp lệ.');
        }

        $sum = 0.0;
        foreach ($breakdown as $item) {
            $maxScore = is_array($item) ? ($item['max_score'] ?? null) : null;
            $itemScore = is_array($item) ? ($item['score'] ?? null) : null;
            if (! is_numeric($maxScore) || ! is_numeric($itemScore) || (float) $maxScore <= 0 || (float) $itemScore < 0 || (float) $itemScore > (float) $maxScore) {
                throw new \UnexpectedValueException('Rubric AI chứa điểm không hợp lệ.');
            }
            $sum += (float) $itemScore;
        }

        if (abs($sum - (float) $score) > 0.11) {
            throw new \UnexpectedValueException('Tổng điểm rubric AI không khớp điểm đề xuất.');
        }

        if (trim((string) ($analysis['feedback'] ?? '')) === '') {
            throw new \UnexpectedValueException('Kết quả chấm AI thiếu nhận xét.');
        }

        return $analysis;
    }

    public function learningAnalysis(array $analysis): array
    {
        if (trim((string) ($analysis['summary'] ?? '')) === '') {
            throw new \UnexpectedValueException('Phân tích học tập AI thiếu phần tóm tắt.');
        }

        foreach (['risks', 'actions', 'student_comments'] as $field) {
            if (! isset($analysis[$field]) || ! is_array($analysis[$field])) {
                throw new \UnexpectedValueException("Phân tích học tập AI thiếu trường {$field}.");
            }
        }

        foreach ($analysis['risks'] as $risk) {
            if (! is_array($risk) || ! in_array($risk['level'] ?? null, ['high', 'medium', 'low'], true) || trim((string) ($risk['reason'] ?? '')) === '') {
                throw new \UnexpectedValueException('Danh sách rủi ro AI không đúng cấu trúc.');
            }
        }

        foreach ($analysis['actions'] as $action) {
            if (! is_array($action) || ! in_array($action['priority'] ?? null, ['high', 'medium', 'low'], true) || trim((string) ($action['action'] ?? '')) === '') {
                throw new \UnexpectedValueException('Danh sách hành động AI không đúng cấu trúc.');
            }
        }

        return $analysis;
    }

    public function teachingDraft(string $type, array $draft): array
    {
        $required = match ($type) {
            'assignment' => ['title', 'type', 'instructions', 'grading_scale', 'grading_rubric'],
            'rubric' => ['grading_scale', 'grading_rubric'],
            'quiz' => ['title', 'time_limit', 'question_distribution', 'topic', 'rationale'],
            'lesson_summary' => ['title', 'content'],
            default => throw new \UnexpectedValueException('Loại bản nháp AI không được hỗ trợ.'),
        };

        foreach ($required as $field) {
            if (! array_key_exists($field, $draft) || (is_string($draft[$field]) && trim($draft[$field]) === '')) {
                throw new \UnexpectedValueException("Bản nháp AI thiếu trường {$field}.");
            }
        }

        if ($type === 'quiz') {
            $types = array_keys(Question::typeLabels());
            $difficulties = ['easy', 'medium', 'hard'];
            $normalized = [];
            foreach ($types as $questionType) {
                foreach ($difficulties as $difficulty) {
                    $value = data_get($draft, "question_distribution.{$questionType}.{$difficulty}", 0);
                    if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0 || (int) $value > 100) {
                        throw new \UnexpectedValueException('Ma trận câu hỏi AI chứa số lượng không hợp lệ.');
                    }
                    $normalized[$questionType][$difficulty] = (int) $value;
                }
            }
            if (collect($normalized)->sum(fn ($counts) => array_sum($counts)) < 1) {
                throw new \UnexpectedValueException('Ma trận câu hỏi AI phải có ít nhất một câu.');
            }
            $draft['question_distribution'] = $normalized;
        }

        if ($type === 'assignment' && ! in_array($draft['type'], ['essay', 'file', 'mixed'], true)) {
            throw new \UnexpectedValueException('Loại bài tập AI không hợp lệ.');
        }

        return $draft;
    }

    public function coursePlan(array $plan, int $sessionCount): array
    {
        if (trim((string) ($plan['summary'] ?? '')) === '' || ! isset($plan['modules']) || ! is_array($plan['modules']) || $plan['modules'] === []) {
            throw new \UnexpectedValueException('Kế hoạch AI thiếu tóm tắt hoặc danh sách chương.');
        }

        $lessonCount = 0;
        foreach ($plan['modules'] as $module) {
            if (! is_array($module) || trim((string) ($module['title'] ?? '')) === '' || empty($module['lessons']) || ! is_array($module['lessons'])) {
                throw new \UnexpectedValueException('Chương trong kế hoạch AI không đúng cấu trúc.');
            }

            foreach ($module['lessons'] as $lesson) {
                if (! is_array($lesson) || trim((string) ($lesson['title'] ?? '')) === '') {
                    throw new \UnexpectedValueException('Bài học trong kế hoạch AI thiếu tiêu đề.');
                }
                $lessonCount++;
            }
        }

        if ($lessonCount !== $sessionCount) {
            throw new \UnexpectedValueException("Kế hoạch AI phải có đúng {$sessionCount} buổi học.");
        }

        return $plan;
    }
}
