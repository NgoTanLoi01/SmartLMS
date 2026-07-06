<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'classes';
    protected $fillable = ['name', 'code', 'teacher_id', 'status'];

    public function scopeNotArchived($query)
    {
        $statusColumn = $query->getModel()->getTable() . '.status';

        return $query->where(function ($q) use ($statusColumn) {
            $q->whereNull($statusColumn)
                ->orWhere($statusColumn, '!=', self::STATUS_ARCHIVED);
        });
    }

    public function scopeVisible($query)
    {
        return $query->where($query->getModel()->getTable() . '.status', self::STATUS_ACTIVE);
    }

    // Một lớp thuộc về một giáo viên
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Một lớp có nhiều học sinh
    public function students()
    {
        return $this->belongsToMany(User::class, 'class_user', 'class_id', 'user_id');
    }
    // Một lớp học có nhiều khóa học
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'class_course', 'class_id', 'course_id');
    }

    public function materialAssignments()
    {
        return $this->hasMany(LearningMaterialAssignment::class, 'class_id');
    }
}
