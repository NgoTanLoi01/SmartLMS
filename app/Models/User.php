<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- DÒNG 1: Import thư viện

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // <--- DÒNG 2: Sử dụng trait này

    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Đảm bảo có role nếu bạn dùng ở bước trước
    ];

    protected $hidden = ['password', 'remember_token'];

    public function submissions()
    {
        return $this->hasMany(Submission::class, 'student_id');
    }

    public function completedLessons()
    {
        return $this->belongsToMany(Lesson::class, 'lesson_user')->withPivot('completed_at');
    }
    // Một User (Teacher) quản lý nhiều lớp
    public function managedClasses()
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    // Một User (Student) tham gia nhiều lớp
    public function classes()
    {
        return $this->belongsToMany(Classroom::class, 'class_user', 'user_id', 'class_id');
    }
    public function lessons()
    {
        return $this->belongsToMany(Lesson::class, 'lesson_user')->withPivot('completed_at')->withTimestamps();
    }
}
