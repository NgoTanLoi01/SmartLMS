<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AttendanceColumn extends Model
{
    protected $fillable = ['course_id', 'name', 'type', 'order'];

    public function data()
    {
        return $this->hasMany(AttendanceData::class);
    }
}
