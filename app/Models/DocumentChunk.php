<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentChunk extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'document_chunks';

    protected $fillable = [
        'document_name', 'content', 'embedding', 'course_id', 'uploaded_by',
        'ingestion_id', 'chunk_index', 'page_number', 'content_hash', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'chunk_index' => 'integer',
        'page_number' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    // XÓA HOẶC COMMENT DÒNG NÀY ĐI
    // protected $casts = ['embedding' => 'array'];
}
