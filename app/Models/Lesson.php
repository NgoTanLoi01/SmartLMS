<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = ['module_id', 'title', 'content', 'video_url', 'attachment_path', 'attachment', 'attachment_disk', 'attachment_original_name', 'attachment_mime_type', 'attachment_size', 'order', 'status', 'published_at', 'available_from'];

    protected $casts = [
        'published_at' => 'datetime',
        'available_from' => 'datetime',
    ];

    public function scopeVisibleToStudents($query)
    {
        $table = $query->getModel()->getTable();

        return $query->where("{$table}.status", self::STATUS_PUBLISHED)
            ->where(function ($q) use ($table) {
                $q->whereNull("{$table}.available_from")
                    ->orWhere("{$table}.available_from", '<=', now());
            });
    }

    public function scopeNotArchived($query)
    {
        $statusColumn = $query->getModel()->getTable().'.status';

        return $query->where(function ($q) use ($statusColumn) {
            $q->whereNull($statusColumn)
                ->orWhere($statusColumn, '!=', self::STATUS_ARCHIVED);
        });
    }

    public function isVisibleToStudents(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && (! $this->available_from || $this->available_from->lte(now()));
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
        return $this->hasMany(Assignments::class, 'lesson_id')->notArchived();
    }

    public function materialAssignments()
    {
        return $this->hasMany(LearningMaterialAssignment::class);
    }
}
