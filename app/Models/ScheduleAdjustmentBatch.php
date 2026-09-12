<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleAdjustmentBatch extends Model
{
    public const STATUS_APPLIED = 'applied';

    public const STATUS_UNDONE = 'undone';

    protected $fillable = [
        'public_id',
        'created_by',
        'date_from',
        'date_to',
        'class_ids',
        'course_ids',
        'shift_unit',
        'shift_amount',
        'shift_days',
        'schedule_count',
        'before_values',
        'after_values',
        'status',
        'undone_by',
        'undone_at',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date:Y-m-d',
            'date_to' => 'date:Y-m-d',
            'class_ids' => 'array',
            'course_ids' => 'array',
            'shift_amount' => 'integer',
            'shift_days' => 'integer',
            'schedule_count' => 'integer',
            'before_values' => 'array',
            'after_values' => 'array',
            'undone_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function undoneBy()
    {
        return $this->belongsTo(User::class, 'undone_by');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
