<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignments extends Model
{
    use SoftDeletes; // Kích hoạt tính năng Xóa mềm (SoftDeletes)

    // Khai báo rõ tên bảng vì tên Model đang là số nhiều
    protected $table = 'assignments';

    protected $fillable = ['course_id', 'lesson_id', 'title', 'instructions', 'due_date', 'allowed_extensions', 'max_file_size', 'status'];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
    }
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
