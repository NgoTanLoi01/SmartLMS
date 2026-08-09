<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradingPeriod extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const MISSING_BLOCK = 'block';

    public const MISSING_EXCLUDE = 'exclude';

    public const MISSING_ZERO = 'zero';

    protected $fillable = [
        'course_id', 'code', 'name', 'starts_at', 'ends_at', 'status', 'missing_policy',
        'rounding_precision', 'rounding_mode', 'calculation_version',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'rounding_precision' => 'integer',
            'calculation_version' => 'integer',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function categories()
    {
        return $this->hasMany(GradeCategory::class)->orderBy('position');
    }

    public function items()
    {
        return $this->hasMany(GradeItem::class)->orderBy('position');
    }

    public function finalizations()
    {
        return $this->hasMany(GradeFinalization::class);
    }
}
