<?php

namespace App\Policies;

use App\Models\LearningProgram;
use App\Models\User;

class LearningProgramPolicy
{
    public function view(User $user, LearningProgram $program): bool
    {
        return $this->update($user, $program);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, LearningProgram $program): bool
    {
        return $user->isAdmin()
            || ($user->isTeacher() && (int) $program->teacher_id === (int) $user->id);
    }

    public function delete(User $user, LearningProgram $program): bool
    {
        return $this->update($user, $program);
    }
}
