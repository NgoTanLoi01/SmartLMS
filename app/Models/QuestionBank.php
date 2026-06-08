<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    protected $fillable = ['name', 'description', 'teacher_id'];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_question_bank')->withTimestamps();
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
