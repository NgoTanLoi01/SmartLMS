<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SharedDocument extends Model
{
    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_TEACHERS = 'teachers';

    protected $fillable = [
        'owner_id',
        'title',
        'description',
        'folder',
        'visibility',
        'disk',
        'file_path',
        'original_name',
        'mime_type',
        'extension',
        'file_size',
        'checksum',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if (! $user->isTeacher()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $access) use ($user) {
            $access->where('owner_id', $user->id)
                ->orWhere('visibility', self::VISIBILITY_TEACHERS);
        });
    }

    public function accessibleBy(User $user): bool
    {
        return self::query()->whereKey($this->id)->accessibleTo($user)->exists();
    }

    public function ownedBy(User $user): bool
    {
        return $user->isAdmin() || (int) $this->owner_id === (int) $user->id;
    }

    public function humanSize(): string
    {
        $size = (float) $this->file_size;

        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($size < 1024 || $unit === 'GB') {
                return rtrim(rtrim(number_format($size, $unit === 'B' ? 0 : 1), '0'), '.').' '.$unit;
            }

            $size /= 1024;
        }

        return $this->file_size.' B';
    }

    public function iconClass(): string
    {
        return match ($this->extension) {
            'pdf' => 'fa-file-pdf',
            'doc', 'docx' => 'fa-file-word',
            'xls', 'xlsx', 'csv' => 'fa-file-excel',
            'ppt', 'pptx' => 'fa-file-powerpoint',
            'html', 'htm' => 'fa-file-code',
            'jpg', 'jpeg', 'png', 'webp' => 'fa-file-image',
            'zip' => 'fa-file-zipper',
            default => 'fa-file-lines',
        };
    }
}
