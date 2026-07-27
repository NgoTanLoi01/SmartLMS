<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttemptAnswer extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'quiz_attempt_question_id',
        'selected_option_id',
        'answer_payload',
        'is_correct',
        'answered_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'answer_payload' => 'array',
        'is_correct' => 'boolean',
    ];

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function attemptQuestion()
    {
        return $this->belongsTo(QuizAttemptQuestion::class, 'quiz_attempt_question_id');
    }
}
