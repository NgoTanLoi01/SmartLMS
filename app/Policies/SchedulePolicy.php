<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function create(User $user, Classroom $classroom, Course $course): bool
    {
        if ($user->isAdmin()) {
            return $classroom->courses()->whereKey($course->id)->exists();
        }

        return $user->can('update', $classroom)
            && $user->can('update', $course)
            && $classroom->courses()->whereKey($course->id)->exists();
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $schedule->classroom
            && $schedule->course
            && $this->create($user, $schedule->classroom, $schedule->course);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $this->update($user, $schedule);
    }
}
