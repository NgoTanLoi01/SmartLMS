<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_ARCHIVED = 'archived';

    // Cập nhật thêm các cột cấu hình số lượng câu hỏi
    protected $fillable = ['course_id', 'title', 'time_limit', 'is_random', 'easy_count', 'medium_count', 'hard_count', 'status', 'published_at', 'available_from'];

    protected $casts = [
        'published_at' => 'datetime',
        'available_from' => 'datetime',
    ];

    public function scopeVisibleToStudents($query)
    {
        $table = $query->getModel()->getTable();

        return $query->where("{$table}.status", self::STATUS_PUBLISHED)
            ->where(function ($q) use ($table) {
                $q->whereNull("{$table}.available_from")
                    ->orWhere("{$table}.available_from", '<=', now());
            });
    }

    public function scopeNotArchived($query)
    {
        $statusColumn = $query->getModel()->getTable() . '.status';

        return $query->where(function ($q) use ($statusColumn) {
            $q->whereNull($statusColumn)
                ->orWhere($statusColumn, '!=', self::STATUS_ARCHIVED);
        });
    }

    public function isVisibleToStudents(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
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
