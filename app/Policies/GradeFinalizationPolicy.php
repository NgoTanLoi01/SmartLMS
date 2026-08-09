<?php

namespace App\Policies;

use App\Models\GradeFinalization;
use App\Models\User;

class GradeFinalizationPolicy
{
    public function view(User $user, GradeFinalization $finalization): bool
    {
        if ($user->isStudent()) {
            return (int) $finalization->user_id === (int) $user->id;
        }

        return $finalization->period?->course && $user->can('view', $finalization->period->course);
    }

    public function reopen(User $user, GradeFinalization $finalization): bool
    {
        return $finalization->period?->course && $user->can('update', $finalization->period->course);
    }
}
