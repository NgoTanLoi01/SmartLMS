<?php

namespace App\Policies;

use App\Models\SharedDocument;
use App\Models\User;

class SharedDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, SharedDocument $document): bool
    {
        return $document->accessibleBy($user);
    }

    public function download(User $user, SharedDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SharedDocument $document): bool
    {
        return $document->ownedBy($user);
    }

    public function delete(User $user, SharedDocument $document): bool
    {
        return $this->update($user, $document);
    }
}
