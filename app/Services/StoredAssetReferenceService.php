<?php

namespace App\Services;

use App\Models\LearningMaterial;
use Illuminate\Support\Facades\Storage;

class StoredAssetReferenceService
{
    public function isIndexed(string $disk, ?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return LearningMaterial::where('disk', $disk)
            ->where('file_path', $path)
            ->exists();
    }

    public function deleteIfUnindexed(string $disk, ?string $path): bool
    {
        if (! $path || $this->isIndexed($disk, $path)) {
            return false;
        }

        return Storage::disk($disk)->delete($path);
    }
}
