<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = ['course_id', 'question_bank_id', 'question_text', 'difficulty', 'status'];

    public function scopeNotArchived($query)
    {
        $statusColumn = $query->getModel()->getTable().'.status';

        return $query->where(function ($q) use ($statusColumn) {
            $q->whereNull($statusColumn)
                ->orWhere($statusColumn, '!=', self::STATUS_ARCHIVED);
        });
    }

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
