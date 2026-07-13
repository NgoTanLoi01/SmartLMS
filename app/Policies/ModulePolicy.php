<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    public function create(User $user, Course $course): bool
    {
        return $user->can('manageContent', $course);
    }

    public function update(User $user, Module $module): bool
    {
        return $module->course && $user->can('manageContent', $module->course);
    }

    public function delete(User $user, Module $module): bool
    {
        return $this->update($user, $module);
    }
}
