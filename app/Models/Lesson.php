<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    // RẤT QUAN TRỌNG: Thêm dòng này để có thể lưu dữ liệu từ Form
    protected $fillable = ['module_id', 'title', 'content', 'video_url', 'attachment_path', 'order'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    // Kiểm tra xem người dùng hiện tại đã hoàn thành bài học này chưa
    public function users()
    {
        return $this->belongsToMany(User::class, 'lesson_user')->withPivot('completed_at')->withTimestamps();
    }
}
