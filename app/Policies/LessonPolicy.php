<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;

class LessonPolicy
{
    public function view(User $user, Lesson $lesson): bool
    {
        $course = $lesson->module?->course;

        if (! $course || ! $user->can('view', $course)) {
            return false;
        }

        return ! $user->isStudent() || $lesson->isVisibleToStudents();
    }

    public function create(User $user, Module $module): bool
    {
        return $module->course && $user->can('manageContent', $module->course);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $lesson->module?->course && $user->can('manageContent', $lesson->module->course);
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }

    public function complete(User $user, Lesson $lesson): bool
    {
        return $user->isStudent() && $this->view($user, $lesson);
    }
}
