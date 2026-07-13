<?php

namespace App\Policies;

use App\Models\DocumentChunk;
use App\Models\User;

class DocumentChunkPolicy
{
    public function delete(User $user, DocumentChunk $document): bool
    {
        return $user->isAdmin()
            || ($document->uploaded_by && (int) $document->uploaded_by === (int) $user->id);
    }
}
