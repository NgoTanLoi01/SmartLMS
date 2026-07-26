<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttemptQuestion extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'position',
        'question_text',
        'passage_title',
        'passage_content',
        'passage_source_label',
        'option_snapshot',
        'correct_option_id',
    ];

    protected $casts = [
        'option_snapshot' => 'array',
    ];

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function answer()
    {
        return $this->hasOne(QuizAttemptAnswer::class);
    }
}
