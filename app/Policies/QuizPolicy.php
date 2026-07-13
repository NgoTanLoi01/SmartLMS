<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function view(User $user, Quiz $quiz): bool
    {
        if (! $quiz->course || ! $user->can('view', $quiz->course)) {
            return false;
        }

        return ! $user->isStudent() || $quiz->isVisibleToStudents();
    }

    public function create(User $user, Course $course): bool
    {
        return $user->can('manageContent', $course);
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $quiz->course && $user->can('manageContent', $quiz->course);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }

    public function viewSubmissions(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }

    public function attempt(User $user, Quiz $quiz): bool
    {
        return $user->isStudent() && $this->view($user, $quiz);
    }
}
