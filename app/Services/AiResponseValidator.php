<?php

namespace App\Services;

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
            $options = $question['options'] ?? null;
            $correctIndex = $question['correct_index'] ?? null;

            if ($text === '' || ! is_array($options) || count($options) !== 4) {
                throw new \UnexpectedValueException('Mỗi câu hỏi AI phải có nội dung và đúng 4 đáp án.');
            }

            $options = collect($options)->map(fn ($option) => trim((string) $option))->values()->all();
            if (collect($options)->contains('') || count(array_unique($options)) !== 4) {
                throw new \UnexpectedValueException('Các đáp án AI phải đầy đủ và không trùng nhau.');
            }

            if (! is_int($correctIndex) || $correctIndex < 0 || $correctIndex > 3) {
                throw new \UnexpectedValueException('Chỉ số đáp án đúng của AI phải nằm trong khoảng 0–3.');
            }

            return [
                'question' => $text,
                'options' => $options,
                'correct_index' => $correctIndex,
                'explanation' => trim((string) ($question['explanation'] ?? '')),
            ];
        })->all();
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
            'quiz' => ['title', 'time_limit', 'easy_count', 'medium_count', 'hard_count', 'topic'],
            'lesson_summary' => ['title', 'content'],
            default => throw new \UnexpectedValueException('Loại bản nháp AI không được hỗ trợ.'),
        };

        foreach ($required as $field) {
            if (! array_key_exists($field, $draft) || (is_string($draft[$field]) && trim($draft[$field]) === '')) {
                throw new \UnexpectedValueException("Bản nháp AI thiếu trường {$field}.");
            }
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
