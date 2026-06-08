<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['course_id', 'question_bank_id', 'question_text', 'difficulty'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }
}
