<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentChunk extends Model
{
    // Dòng quan trọng nhất: Ép Model này luôn dùng Postgres
    protected $connection = 'pgsql';

    protected $table = 'document_chunks';
    protected $fillable = ['document_name', 'content', 'embedding', 'course_id'];
}
