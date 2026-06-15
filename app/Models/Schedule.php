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
        return $query->where(function ($q) {
            $q->whereNull('status')
                ->orWhere('status', '!=', self::STATUS_ARCHIVED);
        });
    }

    public function scopeVisible($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function classroom()
    {
        return $this->belongsTo(ClassManagement::class, 'class_id'); // Tùy tên model Class của bạn
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
