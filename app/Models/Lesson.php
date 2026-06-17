<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['module_id', 'title', 'content', 'video_url', 'attachment_path', 'attachment', 'attachment_disk', 'attachment_original_name', 'attachment_mime_type', 'attachment_size', 'order', 'status', 'published_at', 'available_from'];

    protected $casts = [
        'published_at' => 'datetime',
        'available_from' => 'datetime',
    ];

    public function scopeVisibleToStudents($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            });
    }

    public function isVisibleToStudents(): bool
    {
        return $this->status === 'published'
            && (!$this->available_from || $this->available_from->lte(now()));
    }

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
