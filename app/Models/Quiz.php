<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    // Cập nhật thêm các cột cấu hình số lượng câu hỏi
    protected $fillable = ['course_id', 'title', 'time_limit', 'is_random', 'easy_count', 'medium_count', 'hard_count', 'status', 'published_at', 'available_from'];

    protected $casts = [
        'published_at' => 'datetime',
        'available_from' => 'datetime',
    ];

    public function scopeVisibleToStudents($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            });
    }

    public function isVisibleToStudents(): bool
    {
        return $this->status === 'published'
            && (!$this->available_from || $this->available_from->lte(now()));
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
