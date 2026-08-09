<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class GradeAdjustment extends Model
{
    public const TYPE_BONUS = 'bonus';

    public const TYPE_PENALTY = 'penalty';

    public const TYPE_OVERRIDE = 'override';

    public const TYPE_REVERSAL = 'reversal';

    public const SCOPE_ITEM = 'item';

    public const SCOPE_CATEGORY = 'category';

    public const SCOPE_FINAL = 'final';

    protected $fillable = [
        'grading_period_id', 'user_id', 'grade_id', 'grade_category_id', 'type', 'scope',
        'amount', 'reason', 'adjusted_by', 'adjusted_at', 'reverses_adjustment_id', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'adjusted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Grade adjustment là ledger bất biến; hãy tạo reversal.'));
        static::deleting(fn () => throw new LogicException('Không được xóa grade adjustment.'));
    }

    public function period()
    {
        return $this->belongsTo(GradingPeriod::class, 'grading_period_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function category()
    {
        return $this->belongsTo(GradeCategory::class, 'grade_category_id');
    }
}
