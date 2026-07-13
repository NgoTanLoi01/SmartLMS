<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceColumn extends Model
{
    protected $fillable = ['course_id', 'schedule_id', 'attendance_date', 'name', 'type', 'order'];

    protected $casts = ['attendance_date' => 'date'];

    public function data()
    {
        return $this->hasMany(AttendanceData::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
