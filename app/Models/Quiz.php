<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    // Cập nhật thêm các cột cấu hình số lượng câu hỏi
    protected $fillable = ['course_id', 'title', 'time_limit', 'is_random', 'easy_count', 'medium_count', 'hard_count'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
