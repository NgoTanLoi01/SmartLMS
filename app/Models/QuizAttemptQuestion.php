<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttemptQuestion extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'question_type',
        'grading_mode',
        'max_score',
        'position',
        'question_text',
        'passage_title',
        'passage_content',
        'passage_source_label',
        'option_snapshot',
        'answer_key_snapshot',
        'response_schema_snapshot',
        'correct_option_id',
    ];

    protected $casts = [
        'option_snapshot' => 'array',
        'answer_key_snapshot' => 'array',
        'response_schema_snapshot' => 'array',
    ];

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function answer()
    {
        return $this->hasOne(QuizAttemptAnswer::class);
    }

    public function attachments()
    {
        return $this->hasMany(QuizAttemptAttachment::class);
    }

    public function requiresManualGrading(): bool
    {
        return $this->grading_mode === 'manual';
    }

    public function gradingRubric(): array
    {
        $rubric = collect($this->answer_key_snapshot['rubric'] ?? [])
            ->filter(fn ($criterion) => is_array($criterion) && trim((string) ($criterion['criterion'] ?? '')) !== '')
            ->map(fn ($criterion) => [
                'criterion' => trim((string) $criterion['criterion']),
                'max_score' => max(0, (float) ($criterion['max_score'] ?? 0)),
            ])
            ->values()
            ->all();

        return $rubric ?: [[
            'criterion' => 'Mức độ đáp ứng yêu cầu',
            'max_score' => max(0, (float) $this->max_score),
        ]];
    }

    public function typeLabel(): string
    {
        return Question::typeLabels()[$this->question_type] ?? 'Một đáp án';
    }
}
