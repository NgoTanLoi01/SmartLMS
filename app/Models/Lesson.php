<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['module_id', 'title', 'content', 'video_url', 'attachment_path', 'attachment', 'order'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'lesson_user')->withPivot('completed_at')->withTimestamps();
    }
    public function assignments()
    {
        return $this->hasMany(Assignments::class, 'lesson_id');
    }
}
