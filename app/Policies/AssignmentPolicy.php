<?php

namespace App\Policies;

use App\Models\Assignments;
use App\Models\Course;
use App\Models\User;

class AssignmentPolicy
{
    public function view(User $user, Assignments $assignment): bool
    {
        if (! $assignment->course || ! $user->can('view', $assignment->course)) {
            return false;
        }

        return ! $user->isStudent() || $assignment->isVisibleToStudents();
    }

    public function create(User $user, Course $course): bool
    {
        return $user->can('manageContent', $course);
    }

    public function update(User $user, Assignments $assignment): bool
    {
        return $assignment->course && $user->can('manageContent', $assignment->course);
    }

    public function delete(User $user, Assignments $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function submit(User $user, Assignments $assignment): bool
    {
        return $user->isStudent() && $this->view($user, $assignment);
    }
}
