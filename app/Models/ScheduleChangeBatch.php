<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleChangeBatch extends Model
{
    public const STATUS_APPLIED = 'applied';

    public const STATUS_UNDONE = 'undone';

    protected $fillable = [
        'public_id',
        'created_by',
        'scope',
        'schedule_count',
        'before_values',
        'after_values',
        'status',
        'expires_at',
        'undone_by',
        'undone_at',
    ];

    protected function casts(): array
    {
        return [
            'schedule_count' => 'integer',
            'before_values' => 'array',
            'after_values' => 'array',
            'expires_at' => 'datetime',
            'undone_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
