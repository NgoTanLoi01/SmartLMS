<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignments extends Model
{
    use SoftDeletes; // Kích hoạt tính năng Xóa mềm (SoftDeletes)

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_ARCHIVED = 'archived';

    // Khai báo rõ tên bảng vì tên Model đang là số nhiều
    protected $table = 'assignments';

    protected $fillable = ['course_id', 'lesson_id', 'type', 'title', 'instructions', 'grading_rubric', 'grading_scale', 'ai_grading_enabled', 'due_date', 'allowed_extensions', 'max_file_size', 'status', 'published_at', 'available_from'];

    protected $casts = [
        'due_date' => 'datetime',
        'published_at' => 'datetime',
        'available_from' => 'datetime',
        'ai_grading_enabled' => 'boolean',
        'grading_scale' => 'integer',
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
        $statusColumn = $query->getModel()->getTable().'.status';

        return $query->where(function ($q) use ($statusColumn) {
            $q->whereNull($statusColumn)
                ->orWhere($statusColumn, '!=', self::STATUS_ARCHIVED);
        });
    }

    public function isVisibleToStudents(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && (! $this->available_from || $this->available_from->lte(now()));
    }

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
