<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $connection = 'mysql';

    protected $fillable = ['title', 'description', 'teacher_id', 'learning_program_id', 'course_type', 'status', 'published_at', 'available_from'];

    protected $casts = [
        'published_at' => 'datetime',
        'available_from' => 'datetime',
    ];

    public function scopeVisibleToStudents($query)
    {
        return $query->where('course_type', 'delivery')
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            });
    }

    public function isVisibleToStudents(): bool
    {
        return $this->course_type === 'delivery'
            && $this->status === 'published'
            && (!$this->available_from || $this->available_from->lte(now()));
    }

    public function isTemplate(): bool
    {
        return $this->course_type === 'template';
    }

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    public function assignments()
    {
        return $this->hasMany(Assignments::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function learningProgram()
    {
        return $this->belongsTo(LearningProgram::class);
    }

    public function classes()
    {
        return $this->belongsToMany(Classroom::class, 'class_course', 'course_id', 'class_id');
    }
    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function questionBanks()
    {
        return $this->belongsToMany(QuestionBank::class, 'course_question_bank')->withTimestamps();
    }
    // 1. Lấy tất cả bài học thông qua modules
    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

}
