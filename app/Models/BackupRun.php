<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupRun extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'triggered_by',
        'filename',
        'local_path',
        'remote_disk',
        'remote_path',
        'size_bytes',
        'started_at',
        'finished_at',
        'duration_seconds',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function localFileExists(): bool
    {
        return filled($this->local_path) && is_file($this->local_path);
    }

    public function formattedSize(): string
    {
        $bytes = (int) $this->size_bytes;

        if ($bytes <= 0) {
            return '---';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return number_format($bytes, $index === 0 ? 0 : 1, ',', '.') . ' ' . $units[$index];
    }
}
