<?php

namespace App\Services;

use App\Models\Question;

class AiResponseValidator
{
    public function __construct(private ?HtmlCssCodeFormatter $codeFormatter = null)
    {
        $this->codeFormatter ??= new HtmlCssCodeFormatter;
    }

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
                Question::TYPE_ESSAY => $this->normalizeEssay($question),
                Question::TYPE_CODE_DEBUG => $this->normalizeCodeDebug($question),
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

    private function normalizeEssay(array $question): array
    {
        $maxScore = $this->normalizeManualMaxScore($question);
        $wordLimit = filter_var($question['word_limit'] ?? null, FILTER_VALIDATE_INT);
        if ($wordLimit === false || $wordLimit < 10 || $wordLimit > 5000) {
            throw new \UnexpectedValueException('Câu tự luận cần giới hạn từ trong khoảng 10–5000.');
        }

        return [
            'max_score' => $maxScore,
            'word_limit' => $wordLimit,
            'allow_attachments' => filter_var($question['allow_attachments'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'rubric' => $this->normalizeManualRubric($question, $maxScore),
        ];
    }

    private function normalizeCodeDebug(array $question): array
    {
        $maxScore = $this->normalizeManualMaxScore($question);
        $starterCode = trim((string) ($question['starter_code'] ?? ''));
        if ($starterCode === '' || mb_strlen($starterCode) > 50000) {
            throw new \UnexpectedValueException('Câu sửa lỗi cần có mã HTML/CSS ban đầu, tối đa 50.000 ký tự.');
        }
        if (preg_match('/<\s*script\b|\bon[a-z]+\s*=|javascript\s*:/iu', $starterCode)) {
            throw new \UnexpectedValueException('Bài sửa lỗi HTML/CSS không được chứa JavaScript.');
        }

        $mode = (string) ($question['explanation_mode'] ?? 'required');
        if (! in_array($mode, ['disabled', 'optional', 'required'], true)) {
            throw new \UnexpectedValueException('Chế độ giải thích của câu sửa lỗi không hợp lệ.');
        }

        $wordLimit = $mode === 'disabled'
            ? 0
            : filter_var($question['explanation_word_limit'] ?? null, FILTER_VALIDATE_INT);
        if ($mode !== 'disabled' && ($wordLimit === false || $wordLimit < 10 || $wordLimit > 2000)) {
            throw new \UnexpectedValueException('Giới hạn phần giải thích phải trong khoảng 10–2000 từ.');
        }

        return [
            'max_score' => $maxScore,
            'starter_code' => $this->codeFormatter->format($starterCode),
            'explanation_mode' => $mode,
            'explanation_word_limit' => (int) $wordLimit,
            'rubric' => $this->normalizeManualRubric($question, $maxScore),
        ];
    }

    private function normalizeManualMaxScore(array $question): float
    {
        $maxScore = $question['max_score'] ?? null;
        if (! is_numeric($maxScore) || (float) $maxScore < 0.25 || (float) $maxScore > 100) {
            throw new \UnexpectedValueException('Câu chấm thủ công cần điểm tối đa từ 0,25 đến 100.');
        }

        return (float) $maxScore;
    }

    private function normalizeManualRubric(array $question, float $maxScore): array
    {
        $rubric = $question['rubric'] ?? null;
        if (! is_array($rubric) || ! array_is_list($rubric) || $rubric === [] || count($rubric) > 10) {
            throw new \UnexpectedValueException('Rubric phải có từ 1 đến 10 tiêu chí chấm.');
        }

        $normalized = collect($rubric)->map(function ($item) {
            if (! is_array($item)) {
                throw new \UnexpectedValueException('Mỗi tiêu chí rubric phải có tên và điểm tối đa.');
            }
            $criterion = mb_substr(trim((string) ($item['criterion'] ?? '')), 0, 500);
            $score = $item['max_score'] ?? null;
            if ($criterion === '' || ! is_numeric($score) || (float) $score <= 0) {
                throw new \UnexpectedValueException('Mỗi tiêu chí rubric phải có tên và điểm tối đa hợp lệ.');
            }

            return ['criterion' => $criterion, 'max_score' => (float) $score];
        })->values();

        if ($normalized->pluck('criterion')->map(fn ($value) => mb_strtolower($value))->unique()->count() !== $normalized->count()) {
            throw new \UnexpectedValueException('Các tiêu chí rubric không được trùng nhau.');
        }
        if (abs($normalized->sum('max_score') - $maxScore) > 0.001) {
            throw new \UnexpectedValueException('Tổng điểm rubric phải bằng điểm tối đa của câu.');
        }

        return $normalized->all();
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

    public function coursePlanOutline(array $plan, int $sessionCount): array
    {
        $this->validateCoursePlanEnvelope($plan);

        $lessonCount = 0;
        $titles = [];
        $focuses = [];
        foreach ($plan['modules'] as $module) {
            if (! is_array($module) || trim((string) ($module['title'] ?? '')) === '' || empty($module['lessons']) || ! is_array($module['lessons'])) {
                throw new \UnexpectedValueException('Chương trong kế hoạch AI không đúng cấu trúc.');
            }

            foreach ($module['lessons'] as $lesson) {
                if (! is_array($lesson)) {
                    throw new \UnexpectedValueException('Khung bài học AI không đúng cấu trúc.');
                }
                $title = trim((string) ($lesson['title'] ?? ''));
                $focus = trim((string) ($lesson['focus'] ?? ''));
                $product = trim((string) ($lesson['capstone_product'] ?? ''));
                if ($title === '' || $focus === '' || $product === '') {
                    throw new \UnexpectedValueException('Khung bài học AI thiếu tiêu đề, trọng tâm hoặc sản phẩm cuối khóa.');
                }
                $titles[] = $this->normalizedCoursePlanText($title);
                $focuses[] = $this->normalizedCoursePlanText($focus);
                $lessonCount++;
            }
        }

        if ($lessonCount !== $sessionCount) {
            throw new \UnexpectedValueException("Kế hoạch AI phải có đúng {$sessionCount} buổi học.");
        }
        if (count(array_unique($titles)) !== count($titles) || count(array_unique($focuses)) !== count($focuses)) {
            throw new \UnexpectedValueException('Các bài học AI bị trùng tiêu đề hoặc trọng tâm kiến thức.');
        }

        return $plan;
    }

    public function coursePlanLessonBatch(array $batch, array $expectedTitles): array
    {
        $lessons = $batch['lessons'] ?? null;
        if (! is_array($lessons) || ! array_is_list($lessons) || count($lessons) !== count($expectedTitles)) {
            throw new \UnexpectedValueException('AI trả về sai số lượng bài học chi tiết trong lô.');
        }

        foreach ($lessons as $index => $lesson) {
            $this->validateDetailedCourseLesson($lesson, (string) ($expectedTitles[$index] ?? ''));
        }

        return $lessons;
    }

    public function coursePlan(array $plan, int $sessionCount): array
    {
        $this->validateCoursePlanEnvelope($plan);

        $lessonCount = 0;
        $titles = [];
        $contentSignatures = [];
        foreach ($plan['modules'] as $module) {
            if (! is_array($module) || trim((string) ($module['title'] ?? '')) === '' || empty($module['lessons']) || ! is_array($module['lessons'])) {
                throw new \UnexpectedValueException('Chương trong kế hoạch AI không đúng cấu trúc.');
            }

            foreach ($module['lessons'] as $lesson) {
                $this->validateDetailedCourseLesson($lesson);
                $titles[] = $this->normalizedCoursePlanText((string) $lesson['title']);
                $contentSignatures[] = $this->normalizedCoursePlanText(json_encode($lesson['core_content'], JSON_UNESCAPED_UNICODE) ?: '');
                $lessonCount++;
            }
        }

        if ($lessonCount !== $sessionCount) {
            throw new \UnexpectedValueException("Kế hoạch AI phải có đúng {$sessionCount} buổi học.");
        }
        if (count(array_unique($titles)) !== count($titles) || count(array_unique($contentSignatures)) !== count($contentSignatures)) {
            throw new \UnexpectedValueException('Các bài học AI bị trùng tiêu đề hoặc nội dung cốt lõi.');
        }

        return $plan;
    }

    private function validateCoursePlanEnvelope(array $plan): void
    {
        if (trim((string) ($plan['summary'] ?? '')) === '' || ! isset($plan['modules']) || ! is_array($plan['modules']) || $plan['modules'] === []) {
            throw new \UnexpectedValueException('Kế hoạch AI thiếu tóm tắt hoặc danh sách chương.');
        }
    }

    private function validateDetailedCourseLesson($lesson, string $expectedTitle = ''): void
    {
        if (! is_array($lesson)) {
            throw new \UnexpectedValueException('Bài học chi tiết AI không đúng cấu trúc.');
        }

        $title = trim((string) ($lesson['title'] ?? ''));
        if ($title === '' || ($expectedTitle !== '' && $this->normalizedCoursePlanText($title) !== $this->normalizedCoursePlanText($expectedTitle))) {
            throw new \UnexpectedValueException('AI trả về sai tên bài học trong lô chi tiết.');
        }

        $outcomes = $this->coursePlanStringList($lesson['learning_outcomes'] ?? null, 2, 3, 'Kết quả cần đạt');
        foreach ($outcomes as $outcome) {
            if ($this->coursePlanWordCount($outcome) < 4) {
                throw new \UnexpectedValueException('Mỗi kết quả cần đạt phải mô tả một năng lực có thể đánh giá.');
            }
        }

        $scenario = trim((string) ($lesson['real_world_scenario'] ?? ''));
        $scenarioWords = $this->coursePlanWordCount($scenario);
        if ($scenarioWords < 80 || $scenarioWords > 150) {
            throw new \UnexpectedValueException('Tình huống thực tế phải dài từ 80 đến 150 từ.');
        }

        $core = $lesson['core_content'] ?? null;
        if (! is_array($core) || empty($core['explanations']) || ! is_array($core['explanations'])) {
            throw new \UnexpectedValueException('Nội dung cốt lõi phải có các phần giải thích đầy đủ.');
        }
        $coreWords = 0;
        $exampleCount = 0;
        foreach ($core['explanations'] as $explanation) {
            if (! is_array($explanation)) {
                throw new \UnexpectedValueException('Mục nội dung cốt lõi không đúng cấu trúc.');
            }
            $heading = trim((string) ($explanation['heading'] ?? ''));
            $body = trim((string) ($explanation['body'] ?? ''));
            $example = trim((string) ($explanation['example'] ?? ''));
            if ($heading === '' || $this->coursePlanWordCount($body) < 35) {
                throw new \UnexpectedValueException('Mỗi mục nội dung cốt lõi cần tiêu đề và phần giải thích ít nhất 35 từ.');
            }
            $coreWords += $this->coursePlanWordCount($body.' '.$example);
            $exampleCount += $example !== '' ? 1 : 0;
        }
        if ($coreWords < 160 || $exampleCount < 1) {
            throw new \UnexpectedValueException('Nội dung cốt lõi cần ít nhất 160 từ và có ví dụ thực tế.');
        }

        $this->coursePlanStringList($core['process_steps'] ?? null, 3, 7, 'Quy trình thực hiện');
        $comparison = $core['comparison'] ?? null;
        if ($comparison !== null && $comparison !== []) {
            if (! is_array($comparison)) {
                throw new \UnexpectedValueException('Bảng so sánh trong nội dung cốt lõi không hợp lệ.');
            }
            $headers = $this->coursePlanStringList($comparison['headers'] ?? null, 2, 5, 'Tiêu đề bảng so sánh');
            $rows = $comparison['rows'] ?? null;
            if (! is_array($rows) || count($rows) < 2 || count($rows) > 8) {
                throw new \UnexpectedValueException('Bảng so sánh cần từ 2 đến 8 dòng dữ liệu.');
            }
            foreach ($rows as $row) {
                if (! is_array($row) || count($row) !== count($headers) || collect($row)->contains(fn ($cell) => trim((string) $cell) === '')) {
                    throw new \UnexpectedValueException('Các dòng của bảng so sánh phải đủ dữ liệu theo tiêu đề.');
                }
            }
        }

        $practice = $lesson['practice_task'] ?? null;
        if (! is_array($practice) || $this->coursePlanWordCount((string) ($practice['brief'] ?? '')) < 20) {
            throw new \UnexpectedValueException('Nhiệm vụ thực hành cần mô tả cụ thể ít nhất 20 từ.');
        }
        $this->coursePlanStringList($practice['steps'] ?? null, 3, 8, 'Các bước thực hành');

        $deliverable = $lesson['deliverable'] ?? null;
        if (! is_array($deliverable) || trim((string) ($deliverable['name'] ?? '')) === '') {
            throw new \UnexpectedValueException('Mỗi bài học phải có sản phẩm cần hoàn thành.');
        }
        $this->coursePlanStringList($deliverable['requirements'] ?? null, 2, 6, 'Yêu cầu sản phẩm');

        $this->coursePlanStringList($lesson['self_check_questions'] ?? null, 3, 5, 'Câu hỏi tự kiểm tra');

        $capstone = $lesson['capstone_update'] ?? null;
        if (! is_array($capstone)) {
            throw new \UnexpectedValueException('Bài học thiếu hướng dẫn cập nhật bài tập lớn.');
        }
        $this->coursePlanStringList($capstone['word_report'] ?? null, 1, 4, 'Nội dung báo cáo Word');
        $this->coursePlanStringList($capstone['powerpoint'] ?? null, 1, 4, 'Nội dung PowerPoint');
    }

    private function coursePlanStringList($value, int $min, int $max, string $label): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) < $min || count($value) > $max) {
            throw new \UnexpectedValueException("{$label} phải có từ {$min} đến {$max} mục.");
        }

        $items = array_map(fn ($item) => trim((string) $item), $value);
        if (in_array('', $items, true) || count(array_unique(array_map([$this, 'normalizedCoursePlanText'], $items))) !== count($items)) {
            throw new \UnexpectedValueException("{$label} không được để trống hoặc lặp lại.");
        }

        return $items;
    }

    private function coursePlanWordCount(string $text): int
    {
        preg_match_all('/[\p{L}\p{N}]+(?:[-’\'][\p{L}\p{N}]+)*/u', strip_tags($text), $matches);

        return count($matches[0] ?? []);
    }

    private function normalizedCoursePlanText(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? $text));
    }
}
