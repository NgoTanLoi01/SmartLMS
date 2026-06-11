<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['class_id', 'course_id', 'schedule_date', 'start_time', 'end_time', 'room', 'note'];
    public function classroom()
    {
        return $this->belongsTo(ClassManagement::class, 'class_id'); // Tùy tên model Class của bạn
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
