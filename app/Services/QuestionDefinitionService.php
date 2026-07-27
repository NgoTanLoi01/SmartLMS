<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionDefinitionService
{
    public function validate(Request $request): array
    {
        $common = $request->validate([
            'question_type' => ['required', Rule::in(array_keys(Question::typeLabels()))],
        ]);

        return match ($common['question_type']) {
            Question::TYPE_SINGLE_CHOICE => $this->validateSingleChoice($request),
            Question::TYPE_MULTIPLE_CHOICE => $this->validateMultipleChoice($request),
            Question::TYPE_TRUE_FALSE_GROUP => $this->validateTrueFalseGroup($request),
            Question::TYPE_FILL_BLANK => $this->validateFillBlank($request),
            Question::TYPE_NUMERIC => $this->validateNumeric($request),
        };
    }

    public function syncOptions(Question $question, array $definition): void
    {
        $rows = collect($definition['options'] ?? [])->values();
        $existing = $question->options()->orderBy('id')->get();

        DB::transaction(function () use ($question, $rows, $existing) {
            foreach ($rows as $index => $row) {
                $option = $existing->get($index) ?? $question->options()->make();
                $option->fill([
                    'option_text' => $row['text'],
                    'is_correct' => $row['is_correct'],
                ]);
                $option->save();
            }

            $existing->slice($rows->count())->each->delete();
        });
    }

    private function validateSingleChoice(Request $request): array
    {
        $data = $request->validate([
            'options' => ['required', 'array', 'min:2', 'max:8'],
            'options.*' => ['required', 'string', 'max:2000'],
            'correct_option' => ['required', 'integer'],
        ]);

        $this->ensureOptionIndexExists($data['options'], (int) $data['correct_option'], 'correct_option');

        return [
            'question_type' => Question::TYPE_SINGLE_CHOICE,
            'answer_config' => null,
            'options' => $this->mapOptions($data['options'], [(int) $data['correct_option']]),
        ];
    }

    private function validateMultipleChoice(Request $request): array
    {
        $data = $request->validate([
            'options' => ['required', 'array', 'min:2', 'max:8'],
            'options.*' => ['required', 'string', 'max:2000'],
            'correct_options' => ['required', 'array', 'min:2'],
            'correct_options.*' => ['integer', 'distinct'],
        ]);
        $correct = array_map('intval', $data['correct_options']);
        foreach ($correct as $index) {
            $this->ensureOptionIndexExists($data['options'], $index, 'correct_options');
        }

        if (count($correct) >= count($data['options'])) {
            throw ValidationException::withMessages([
                'correct_options' => 'Câu nhiều đáp án phải có ít nhất một phương án nhiễu.',
            ]);
        }

        return [
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'answer_config' => ['grading' => 'all_or_nothing'],
            'options' => $this->mapOptions($data['options'], $correct),
        ];
    }

    private function validateTrueFalseGroup(Request $request): array
    {
        $data = $request->validate([
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*' => ['required', 'string', 'max:2000'],
            'truth_values' => ['required', 'array'],
            'truth_values.*' => ['required', Rule::in(['0', '1', 0, 1, false, true])],
        ]);

        foreach (array_keys($data['options']) as $index) {
            if (! array_key_exists($index, $data['truth_values'])) {
                throw ValidationException::withMessages([
                    'truth_values' => 'Mỗi nhận định cần được xác định là Đúng hoặc Sai.',
                ]);
            }
        }

        return [
            'question_type' => Question::TYPE_TRUE_FALSE_GROUP,
            'answer_config' => ['grading' => 'all_or_nothing'],
            'options' => collect($data['options'])->map(fn ($text, $index) => [
                'text' => trim($text),
                'is_correct' => (bool) $data['truth_values'][$index],
            ])->values()->all(),
        ];
    }

    private function validateFillBlank(Request $request): array
    {
        $data = $request->validate([
            'question_text' => ['required', 'string', 'max:10000'],
            'blank_answers' => ['required', 'array', 'min:1', 'max:10'],
            'blank_answers.*' => ['required', 'string', 'max:2000'],
            'case_sensitive' => ['nullable', 'boolean'],
        ]);
        preg_match_all('/\[\[\s*\d+\s*\]\]/u', $data['question_text'], $matches);
        $placeholderCount = count($matches[0]);
        $placeholderNumbers = collect($matches[0])->map(function ($placeholder) {
            preg_match('/\d+/', $placeholder, $number);

            return (int) ($number[0] ?? 0);
        })->values()->all();

        if ($placeholderCount === 0 || $placeholderCount !== count($data['blank_answers']) || $placeholderNumbers !== range(1, $placeholderCount)) {
            throw ValidationException::withMessages([
                'question_text' => 'Các ký hiệu ô trống phải theo đúng thứ tự [[1]], [[2]]... và khớp với số đáp án.',
            ]);
        }

        $blanks = collect($data['blank_answers'])->values()->map(function ($answers, $index) {
            $accepted = collect(explode('|', $answers))->map(fn ($answer) => trim($answer))->filter()->unique()->values();
            if ($accepted->isEmpty()) {
                throw ValidationException::withMessages([
                    "blank_answers.{$index}" => 'Mỗi ô trống cần có ít nhất một đáp án được chấp nhận.',
                ]);
            }

            return ['accepted' => $accepted->all()];
        })->all();

        return [
            'question_type' => Question::TYPE_FILL_BLANK,
            'answer_config' => [
                'blanks' => $blanks,
                'case_sensitive' => (bool) ($data['case_sensitive'] ?? false),
            ],
            'options' => [],
        ];
    }

    private function validateNumeric(Request $request): array
    {
        $data = $request->validate([
            'numeric_answer' => ['required', 'numeric'],
            'numeric_tolerance' => ['required', 'numeric', 'min:0'],
            'numeric_unit' => ['nullable', 'string', 'max:50'],
        ]);

        return [
            'question_type' => Question::TYPE_NUMERIC,
            'answer_config' => [
                'target' => (float) $data['numeric_answer'],
                'tolerance' => (float) $data['numeric_tolerance'],
                'unit' => trim((string) ($data['numeric_unit'] ?? '')),
            ],
            'options' => [],
        ];
    }

    private function mapOptions(array $options, array $correctIndexes): array
    {
        return collect($options)->map(fn ($text, $index) => [
            'text' => trim($text),
            'is_correct' => in_array((int) $index, $correctIndexes, true),
        ])->values()->all();
    }

    private function ensureOptionIndexExists(array $options, int $index, string $field): void
    {
        if (! array_key_exists($index, $options)) {
            throw ValidationException::withMessages([
                $field => 'Đáp án đúng không thuộc danh sách phương án.',
            ]);
        }
    }
}
