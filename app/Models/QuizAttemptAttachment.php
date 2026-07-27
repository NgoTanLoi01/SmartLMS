<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttemptAttachment extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'quiz_attempt_question_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
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
