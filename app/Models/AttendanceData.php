<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AttendanceData extends Model
{
    protected $table = 'attendance_data';
    protected $fillable = ['attendance_column_id', 'user_id', 'value', 'note'];
}
