<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningMaterialSource extends Model
{
    public const LESSON_ATTACHMENT = 'lesson_attachment';

    public const LESSON_CONTENT = 'lesson_content_asset';

    public const ASSIGNMENT_CONTENT = 'assignment_content_asset';

    protected $fillable = [
        'learning_material_id',
        'course_id',
        'source_type',
        'source_id',
    ];

    public function material()
    {
        return $this->belongsTo(LearningMaterial::class, 'learning_material_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
