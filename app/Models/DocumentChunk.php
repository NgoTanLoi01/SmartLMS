<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentChunk extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'document_chunks';

    protected $fillable = ['document_name', 'content', 'embedding', 'course_id', 'uploaded_by'];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    // XÓA HOẶC COMMENT DÒNG NÀY ĐI
    // protected $casts = ['embedding' => 'array'];
}
