<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    public function view(User $user, Grade $grade): bool
    {
        if ($user->isStudent()) {
            return (int) $grade->user_id === (int) $user->id
                && (bool) $grade->item?->is_published;
        }

        return $grade->item?->course && $user->can('view', $grade->item->course);
    }

    public function update(User $user, Grade $grade): bool
    {
        return $grade->item?->course && $user->can('update', $grade->item->course);
    }
}
