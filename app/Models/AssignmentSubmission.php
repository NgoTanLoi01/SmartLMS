<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    protected $fillable = ['assignment_id', 'user_id', 'file_path', 'text_answer', 'grade', 'feedback', 'submitted_at'];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignments::class, 'assignment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
