<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TEACHER);
    }

    public function view(User $user, Classroom $classroom): bool
    {
        return $user->isAdmin() || $this->ownsClassroom($user, $classroom);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TEACHER);
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $this->view($user, $classroom);
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $this->update($user, $classroom);
    }

    public function manageStudents(User $user, Classroom $classroom): bool
    {
        return $this->update($user, $classroom);
    }

    private function ownsClassroom(User $user, Classroom $classroom): bool
    {
        return $user->isTeacher() && (int) $classroom->teacher_id === (int) $user->id;
    }
}
