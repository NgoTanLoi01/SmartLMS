<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizSession extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const RELEASE_IMMEDIATE = 'immediate';

    public const RELEASE_AFTER_SESSION = 'after_session';

    public const RELEASE_MANUAL = 'manual';

    protected $fillable = [
        'quiz_id',
        'name',
        'starts_at',
        'ends_at',
        'status',
        'result_release_policy',
        'results_released_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'results_released_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function candidates()
    {
        return $this->belongsToMany(User::class, 'quiz_session_user')
            ->withPivot('extra_time_minutes')
            ->withTimestamps();
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_OPEN], true)
            && $this->starts_at->lte(now())
            && $this->ends_at->gte(now());
    }

    public function resultsAreReleased(): bool
    {
        if ($this->results_released_at?->lte(now())) {
            return true;
        }

        return $this->result_release_policy === self::RELEASE_IMMEDIATE
            || ($this->result_release_policy === self::RELEASE_AFTER_SESSION && $this->ends_at->isPast());
    }
}
