<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = ['class_id', 'course_id', 'schedule_date', 'start_time', 'end_time', 'room', 'note', 'status'];

    public function scopeNotArchived($query)
    {
        $statusColumn = $query->getModel()->getTable().'.status';

        return $query->where(function ($q) use ($statusColumn) {
            $q->whereNull($statusColumn)
                ->orWhere($statusColumn, '!=', self::STATUS_ARCHIVED);
        });
    }

    public function scopeVisible($query)
    {
        return $query->where($query->getModel()->getTable().'.status', self::STATUS_ACTIVE);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
