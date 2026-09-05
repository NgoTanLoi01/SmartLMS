<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionVersion;
use Illuminate\Support\Facades\Schema;

class QuestionVersionService
{
    public function available(): bool
    {
        return Schema::hasTable('question_versions')
            && Schema::hasColumns('questions', ['tags', 'current_version']);
    }

    public function snapshot(Question $question): array
    {
        if (! $this->available()) {
            return [];
        }

        $question->loadMissing('options');

        return [
            'course_id' => $question->course_id,
            'question_bank_id' => $question->question_bank_id,
            'quiz_passage_id' => $question->quiz_passage_id,
            'question_type' => $question->question_type,
            'question_text' => $question->question_text,
            'answer_config' => $question->answer_config,
            'difficulty' => $question->difficulty,
            'tags' => array_values($question->tags ?? []),
            'status' => $question->status,
            'options' => $question->options->sortBy('id')->map(fn ($option) => [
                'option_text' => $option->option_text,
                'is_correct' => (bool) $option->is_correct,
            ])->values()->all(),
        ];
    }

    public function recordCreated(Question $question, string $summary = 'Tạo câu hỏi.'): void
    {
        if (! $this->available()) {
            return;
        }

        QuestionVersion::query()->firstOrCreate(
            ['question_id' => $question->id, 'version_number' => 1],
            [
                'snapshot' => $this->snapshot($question->fresh('options')),
                'changed_by' => auth()->id(),
                'change_type' => 'created',
                'change_summary' => $summary,
            ]
        );
    }

    public function recordChange(Question $question, array $previousSnapshot, string $changeType, string $summary): void
    {
        if (! $this->available()) {
            return;
        }

        $previousVersion = max(1, (int) (Question::query()
            ->whereKey($question->getKey())
            ->lockForUpdate()
            ->value('current_version') ?: 1));
        QuestionVersion::query()->firstOrCreate(
            ['question_id' => $question->id, 'version_number' => $previousVersion],
            [
                'snapshot' => $previousSnapshot,
                'changed_by' => null,
                'change_type' => 'baseline',
                'change_summary' => 'Phiên bản trước thay đổi.',
            ]
        );

        $newVersion = $previousVersion + 1;
        $question->syncOriginalAttribute('current_version', $previousVersion);
        $question->forceFill(['current_version' => $newVersion])->saveQuietly();
        QuestionVersion::query()->create([
            'question_id' => $question->id,
            'version_number' => $newVersion,
            'snapshot' => $this->snapshot($question->fresh('options')),
            'changed_by' => auth()->id(),
            'change_type' => $changeType,
            'change_summary' => $summary,
        ]);
    }
}
