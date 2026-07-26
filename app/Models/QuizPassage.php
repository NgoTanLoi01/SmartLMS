<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizPassage extends Model
{
    protected $fillable = ['course_id', 'title', 'content', 'source_label'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
