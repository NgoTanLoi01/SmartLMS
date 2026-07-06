<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LearningMaterial extends Model
{
    public const SOURCE_FILE = 'file';
    public const SOURCE_LINK = 'link';

    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'title',
        'description',
        'type',
        'source_type',
        'disk',
        'file_path',
        'url',
        'original_name',
        'mime_type',
        'file_size',
        'uploaded_by',
        'status',
    ];

    public function assignments()
    {
        return $this->hasMany(LearningMaterialAssignment::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeNotArchived($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status')
                ->orWhere('status', '!=', self::STATUS_ARCHIVED);
        });
    }

    public function isFile(): bool
    {
        return $this->source_type === self::SOURCE_FILE;
    }

    public function isLink(): bool
    {
        return $this->source_type === self::SOURCE_LINK;
    }

    public function downloadUrl(?LearningMaterialAssignment $assignment = null): string
    {
        if ($this->isLink()) {
            return (string) $this->url;
        }

        if ($assignment) {
            return route('materials.download', $assignment);
        }

        return '#';
    }

    public function fileExists(): bool
    {
        return $this->isFile()
            && filled($this->disk)
            && filled($this->file_path)
            && Storage::disk($this->disk)->exists($this->file_path);
    }

    public function humanSize(): string
    {
        if (!$this->file_size) {
            return $this->isLink() ? 'Liên kết' : 'Không rõ dung lượng';
        }

        $size = (float) $this->file_size;
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($size < 1024 || $unit === 'GB') {
                return rtrim(rtrim(number_format($size, $unit === 'B' ? 0 : 1), '0'), '.') . ' ' . $unit;
            }
            $size /= 1024;
        }

        return $this->file_size . ' B';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'pdf' => 'PDF',
            'slide' => 'Slide',
            'video' => 'Video',
            'website' => 'Website',
            'code' => 'Code mẫu',
            'image' => 'Hình ảnh',
            'document' => 'Tài liệu',
            default => 'Học liệu',
        };
    }

    public function iconClass(): string
    {
        return match ($this->type) {
            'pdf' => 'fa-file-pdf',
            'slide' => 'fa-file-powerpoint',
            'video' => 'fa-circle-play',
            'website' => 'fa-link',
            'code' => 'fa-file-code',
            'image' => 'fa-file-image',
            default => $this->isLink() ? 'fa-link' : 'fa-file-lines',
        };
    }
}
