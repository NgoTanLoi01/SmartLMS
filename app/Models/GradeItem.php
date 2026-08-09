<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeItem extends Model
{
    public const TYPE_MANUAL = 'manual';

    public const TYPE_HS1 = 'hs1';

    public const TYPE_HS2 = 'hs2';

    public const TYPE_ASSIGNMENT = 'assignment';

    public const TYPE_QUIZ = 'quiz';

    public const TYPE_EXAM = 'exam';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_LEGACY_ATTENDANCE = 'legacy_attendance';

    public const SOURCE_ASSIGNMENT = 'assignment';

    public const SOURCE_QUIZ = 'quiz';

    protected $fillable = [
        'course_id', 'grading_period_id', 'grade_category_id', 'code', 'name', 'item_type',
        'source_type', 'source_id', 'max_points', 'item_weight', 'attempt_policy', 'absence_policy', 'due_at',
        'position', 'is_published', 'is_locked', 'version',
    ];

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'max_points' => 'decimal:4',
            'item_weight' => 'decimal:4',
            'due_at' => 'datetime',
            'position' => 'integer',
            'is_published' => 'boolean',
            'is_locked' => 'boolean',
            'version' => 'integer',
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

    public function category()
    {
        return $this->belongsTo(GradeCategory::class, 'grade_category_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }
}
