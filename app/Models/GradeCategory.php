<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeCategory extends Model
{
    public const AGGREGATION_WEIGHTED_MEAN = 'weighted_mean';

    protected $fillable = [
        'course_id', 'grading_period_id', 'code', 'name', 'weight_percent',
        'aggregation_method', 'allow_over_max', 'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight_percent' => 'decimal:4',
            'allow_over_max' => 'boolean',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function period()
    {
        return $this->belongsTo(GradingPeriod::class, 'grading_period_id');
    }

    public function items()
    {
        return $this->hasMany(GradeItem::class)->orderBy('position');
    }
}
