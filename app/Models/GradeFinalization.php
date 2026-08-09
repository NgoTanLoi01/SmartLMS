<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeFinalization extends Model
{
    public const STATE_DRAFT = 'draft';

    public const STATE_FINALIZED = 'finalized';

    public const STATE_REOPENED = 'reopened';

    protected $fillable = [
        'course_id', 'grading_period_id', 'user_id', 'state', 'final_score', 'unrounded_score',
        'formula_snapshot', 'grade_snapshot', 'calculation_hash', 'version', 'finalized_by',
        'finalized_at', 'reopened_by', 'reopened_at', 'reopen_reason',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:4',
            'unrounded_score' => 'decimal:8',
            'formula_snapshot' => 'array',
            'grade_snapshot' => 'array',
            'version' => 'integer',
            'finalized_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function period()
    {
        return $this->belongsTo(GradingPeriod::class, 'grading_period_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
