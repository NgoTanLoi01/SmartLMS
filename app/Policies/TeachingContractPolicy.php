<?php

namespace App\Policies;

use App\Models\TeachingContract;
use App\Models\User;

class TeachingContractPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, TeachingContract $contract): bool
    {
        return $user->isAdmin()
            || ($user->isTeacher() && (int) $contract->teacher_id === (int) $user->id);
    }

    public function delete(User $user, TeachingContract $contract): bool
    {
        return $this->update($user, $contract);
    }
}
