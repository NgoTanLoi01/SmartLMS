<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['title', 'description', 'teacher_id'];

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classes()
    {
        return $this->belongsToMany(Classroom::class, 'class_course', 'course_id', 'class_id');
    }
    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }
}
