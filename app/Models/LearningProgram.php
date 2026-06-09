<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningProgram extends Model
{
    protected $fillable = [
        'teacher_id',
        'name',
        'code',
        'description',
        'status',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
