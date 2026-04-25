<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    // 1. Phải khai báo cột student_answers ở đây thì Laravel mới cho phép lưu
    protected $fillable = [
        'quiz_id',
        'user_id',
        'score',
        'student_answers', // <--- THỦ PHẠM LÀ DO THIẾU DÒNG NÀY!
        'started_at',
        'completed_at',
    ];

    // 2. Ép kiểu JSON trong Database thành mảng PHP
    protected $casts = [
        'student_answers' => 'array',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
