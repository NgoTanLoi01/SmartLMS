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
        'grading_status',
        'score',
        'rubric_scores',
        'teacher_feedback',
        'graded_by',
        'graded_at',
        'answered_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'answer_payload' => 'array',
        'is_correct' => 'boolean',
        'rubric_scores' => 'array',
        'graded_at' => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function attemptQuestion()
    {
        return $this->belongsTo(QuizAttemptQuestion::class, 'quiz_attempt_question_id');
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
