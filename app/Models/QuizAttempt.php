<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    // 1. Phải khai báo cột student_answers ở đây thì Laravel mới cho phép lưu
    protected $fillable = [
        'quiz_id',
        'quiz_session_id',
        'user_id',
        'status',
        'score',
        'student_answers',
        'started_at',
        'expires_at',
        'last_seen_at',
        'current_position',
        'flagged_question_ids',
        'completed_at',
        'result_released_at',
    ];

    // 2. Ép kiểu JSON trong Database thành mảng PHP
    protected $casts = [
        'student_answers' => 'array',
        'flagged_question_ids' => 'array',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'completed_at' => 'datetime',
        'result_released_at' => 'datetime',
    ];

    public function scopeResultsReleased($query)
    {
        return $query->whereNotNull('completed_at')->where(function ($query) {
            $query->whereNull('quiz_session_id')
                ->orWhere('result_released_at', '<=', now())
                ->orWhereHas('session', function ($sessionQuery) {
                    $sessionQuery->where('result_release_policy', QuizSession::RELEASE_IMMEDIATE)
                        ->orWhere('results_released_at', '<=', now())
                        ->orWhere(function ($afterSession) {
                            $afterSession->where('result_release_policy', QuizSession::RELEASE_AFTER_SESSION)
                                ->where('ends_at', '<=', now());
                        });
                });
        });
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(QuizSession::class, 'quiz_session_id');
    }

    public function attemptQuestions()
    {
        return $this->hasMany(QuizAttemptQuestion::class)->orderBy('position');
    }

    public function answers()
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress' && $this->completed_at === null;
    }

    public function resultIsReleased(): bool
    {
        if (! $this->session) {
            return true;
        }

        return $this->result_released_at?->lte(now()) || $this->session->resultsAreReleased();
    }
}
