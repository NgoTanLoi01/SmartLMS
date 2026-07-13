<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningMaterialAssignment extends Model
{
    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'learning_material_id',
        'course_id',
        'class_id',
        'lesson_id',
        'unlock_when_lesson_id',
        'available_from',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'available_from' => 'datetime',
    ];

    public function material()
    {
        return $this->belongsTo(LearningMaterial::class, 'learning_material_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function unlockLesson()
    {
        return $this->belongsTo(Lesson::class, 'unlock_when_lesson_id');
    }

    public function scopeNotArchived($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status')
                ->orWhere('status', '!=', self::STATUS_ARCHIVED);
        });
    }

    public function isPublishedNow(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && (! $this->available_from || $this->available_from->lte(now()))
            && $this->material
            && $this->material->status !== LearningMaterial::STATUS_ARCHIVED;
    }

    public function visibleToStudent(User $student): bool
    {
        if (! $this->isPublishedNow()) {
            return false;
        }

        $course = $this->course;
        if (! $course || ! $course->isVisibleToStudents()) {
            return false;
        }

        $studentClassIds = $student->classes()
            ->where('classes.status', Classroom::STATUS_ACTIVE)
            ->pluck('classes.id');

        $hasCourseAccess = $course->classes()
            ->where('classes.status', Classroom::STATUS_ACTIVE)
            ->whereIn('classes.id', $studentClassIds)
            ->exists();

        if (! $hasCourseAccess) {
            return false;
        }

        if ($this->class_id && ! $studentClassIds->contains((int) $this->class_id)) {
            return false;
        }

        if ($this->lesson && ! $this->lesson->isVisibleToStudents()) {
            return false;
        }

        if ($this->unlockLesson && ! $this->unlockLesson->isVisibleToStudents()) {
            return false;
        }

        return true;
    }

    public function lockLabel(): ?string
    {
        if ($this->status === self::STATUS_HIDDEN) {
            return 'Đang ẩn';
        }

        if ($this->available_from && $this->available_from->isFuture()) {
            return 'Mở '.$this->available_from->format('d/m/Y H:i');
        }

        if ($this->unlockLesson) {
            return 'Mở khi tới bài: '.$this->unlockLesson->title;
        }

        return null;
    }
}
