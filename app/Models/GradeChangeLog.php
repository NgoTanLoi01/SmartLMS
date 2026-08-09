<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class GradeChangeLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'grade_id', 'grade_item_id', 'grading_period_id', 'user_id', 'actor_id', 'action',
        'before', 'after', 'reason', 'source', 'correlation_id', 'request_id',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Grade change log là append-only.'));
        static::deleting(fn () => throw new LogicException('Không được xóa grade change log.'));
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function item()
    {
        return $this->belongsTo(GradeItem::class, 'grade_item_id');
    }
}
