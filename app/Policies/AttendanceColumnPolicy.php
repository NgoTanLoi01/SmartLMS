<?php

namespace App\Policies;

use App\Models\AttendanceColumn;
use App\Models\Course;
use App\Models\User;

class AttendanceColumnPolicy
{
    public function create(User $user, Course $course): bool
    {
        return $user->can('manageAttendance', $course);
    }

    public function update(User $user, AttendanceColumn $column): bool
    {
        return $column->course && $user->can('manageAttendance', $column->course);
    }

    public function delete(User $user, AttendanceColumn $column): bool
    {
        return $this->update($user, $column);
    }
}
