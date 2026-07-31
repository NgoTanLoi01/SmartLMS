<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_PENDING_GRADING = 'pending_grading';

    public const STATUS_GRADED = 'graded';

    public const STATUS_RELEASED = 'released';

    // 1. Phải khai báo cột student_answers ở đây thì Laravel mới cho phép lưu
    protected $fillable = [
        'quiz_id',
        'quiz_session_id',
        'user_id',
        'attempt_number',
        'status',
        'score',
        'auto_score',
        'manual_score',
        'student_answers',
        'started_at',
        'expires_at',
        'last_seen_at',
        'current_position',
        'flagged_question_ids',
        'completed_at',
        'graded_at',
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
        'graded_at' => 'datetime',
        'result_released_at' => 'datetime',
    ];

    public function scopeResultsReleased($query)
    {
        return $query->whereNotNull('completed_at')
            ->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_GRADED, self::STATUS_RELEASED])
            ->where(function ($query) {
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

    public function attachments()
    {
        return $this->hasMany(QuizAttemptAttachment::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS && $this->completed_at === null;
    }

    public function resultIsReleased(): bool
    {
        if ($this->status === self::STATUS_PENDING_GRADING || $this->score === null) {
            return false;
        }

        if (! $this->session) {
            return true;
        }

        return $this->result_released_at?->lte(now()) || $this->session->resultsAreReleased();
    }
}
