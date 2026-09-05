<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionVersion extends Model
{
    protected $fillable = [
        'question_id',
        'version_number',
        'snapshot',
        'changed_by',
        'change_type',
        'change_summary',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'snapshot' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
