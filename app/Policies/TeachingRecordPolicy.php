<?php

namespace App\Policies;

use App\Models\TeachingRecord;
use App\Models\User;

class TeachingRecordPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, TeachingRecord $record): bool
    {
        return $user->isAdmin()
            || ($user->isTeacher() && (int) $record->teacher_id === (int) $user->id);
    }

    public function delete(User $user, TeachingRecord $record): bool
    {
        return $this->update($user, $record);
    }
}
