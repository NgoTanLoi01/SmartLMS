<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    public const TYPE_SINGLE_CHOICE = 'single_choice';

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_TRUE_FALSE_GROUP = 'true_false_group';

    public const TYPE_FILL_BLANK = 'fill_blank';

    public const TYPE_NUMERIC = 'numeric';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = ['course_id', 'question_bank_id', 'quiz_passage_id', 'question_type', 'question_text', 'answer_config', 'difficulty', 'observed_difficulty', 'difficulty_metrics', 'difficulty_evaluated_at', 'status'];

    protected $casts = [
        'answer_config' => 'array',
        'difficulty_metrics' => 'array',
        'difficulty_evaluated_at' => 'datetime',
    ];

    public static function typeLabels(): array
    {
        return [
            self::TYPE_SINGLE_CHOICE => 'Một đáp án',
            self::TYPE_MULTIPLE_CHOICE => 'Nhiều đáp án',
            self::TYPE_TRUE_FALSE_GROUP => 'Đúng/Sai theo nhóm',
            self::TYPE_FILL_BLANK => 'Điền khuyết',
            self::TYPE_NUMERIC => 'Trả lời số',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->question_type] ?? 'Một đáp án';
    }

    public function observedDifficultyLabel(): ?string
    {
        return match ($this->observed_difficulty) {
            'easy' => 'Dễ',
            'medium' => 'Trung bình',
            'hard' => 'Khó',
            default => null,
        };
    }

    public function answerSummary(): string
    {
        return match ($this->question_type) {
            self::TYPE_MULTIPLE_CHOICE => $this->options->where('is_correct', true)->pluck('option_text')->implode(' · '),
            self::TYPE_TRUE_FALSE_GROUP => $this->options->map(fn ($option) => $option->option_text.': '.($option->is_correct ? 'Đúng' : 'Sai'))->implode(' · '),
            self::TYPE_FILL_BLANK => collect($this->answer_config['blanks'] ?? [])->map(fn ($blank) => collect($blank['accepted'] ?? [])->first())->filter()->implode(' · '),
            self::TYPE_NUMERIC => (string) ($this->answer_config['target'] ?? ''),
            default => (string) ($this->options->firstWhere('is_correct', true)?->option_text ?? ''),
        } ?: 'Chưa cấu hình đáp án';
    }

    public function scopeReadyForExam($query)
    {
        return $query->where(function ($ready) {
            $ready->where(function ($choice) {
                $choice->whereIn('question_type', [self::TYPE_SINGLE_CHOICE, self::TYPE_MULTIPLE_CHOICE])
                    ->whereHas('options', fn ($options) => $options->where('is_correct', true));
            })->orWhere(function ($group) {
                $group->where('question_type', self::TYPE_TRUE_FALSE_GROUP)->whereHas('options');
            })->orWhere(function ($structured) {
                $structured->whereIn('question_type', [self::TYPE_FILL_BLANK, self::TYPE_NUMERIC])
                    ->whereNotNull('answer_config');
            });
        });
    }

    public function scopeNotArchived($query)
    {
        $statusColumn = $query->getModel()->getTable().'.status';

        return $query->where(function ($q) use ($statusColumn) {
            $q->whereNull($statusColumn)
                ->orWhere($statusColumn, '!=', self::STATUS_ARCHIVED);
        });
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    public function passage()
    {
        return $this->belongsTo(QuizPassage::class, 'quiz_passage_id');
    }
}
