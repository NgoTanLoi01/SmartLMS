<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
{
    public function view(User $user, QuizAttempt $attempt): bool
    {
        if ((int) $attempt->user_id === (int) $user->id) {
            return true;
        }

        return $attempt->quiz?->course
            && $user->can('manageContent', $attempt->quiz->course);
    }
}
