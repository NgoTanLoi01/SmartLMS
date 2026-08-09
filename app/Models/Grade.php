<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    public const STATUS_UNGRADED = 'ungraded';

    public const STATUS_MISSING = 'missing';

    public const STATUS_EXCUSED = 'excused';

    public const STATUS_GRADED = 'graded';

    public const STATUS_EXCLUDED = 'excluded';

    protected $fillable = [
        'grade_item_id', 'user_id', 'status', 'raw_points', 'effective_points',
        'source_version', 'graded_by', 'graded_at', 'version',
    ];

    protected function casts(): array
    {
        return [
            'raw_points' => 'decimal:4',
            'effective_points' => 'decimal:4',
            'graded_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function item()
    {
        return $this->belongsTo(GradeItem::class, 'grade_item_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function adjustments()
    {
        return $this->hasMany(GradeAdjustment::class);
    }
}
