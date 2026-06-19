<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_STUDENT);
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->isAdmin() || $this->ownsCourse($user, $course)) {
            return true;
        }

        if (!$user->isStudent() || !$course->isVisibleToStudents()) {
            return false;
        }

        $studentClassIds = $user->classes()
            ->where('classes.status', Classroom::STATUS_ACTIVE)
            ->pluck('classes.id');

        return $course->classes()
            ->where('classes.status', Classroom::STATUS_ACTIVE)
            ->whereIn('classes.id', $studentClassIds)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TEACHER);
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isAdmin() || $this->ownsCourse($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    public function manageContent(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    private function ownsCourse(User $user, Course $course): bool
    {
        return $user->isTeacher() && (int) $course->teacher_id === (int) $user->id;
    }
}
