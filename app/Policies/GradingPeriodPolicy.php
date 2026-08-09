<?php

namespace App\Policies;

use App\Models\GradingPeriod;
use App\Models\User;

class GradingPeriodPolicy
{
    public function view(User $user, GradingPeriod $period): bool
    {
        return $period->course && $user->can('view', $period->course);
    }

    public function update(User $user, GradingPeriod $period): bool
    {
        return $period->course && $user->can('update', $period->course);
    }

    public function finalize(User $user, GradingPeriod $period): bool
    {
        return $this->update($user, $period);
    }
}
