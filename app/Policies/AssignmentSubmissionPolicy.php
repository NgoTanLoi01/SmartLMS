<?php

namespace App\Policies;

use App\Models\AssignmentSubmission;
use App\Models\User;

class AssignmentSubmissionPolicy
{
    public function view(User $user, AssignmentSubmission $submission): bool
    {
        if ((int) $submission->user_id === (int) $user->id) {
            return true;
        }

        return $submission->assignment?->course
            && $user->can('manageContent', $submission->assignment->course);
    }

    public function grade(User $user, AssignmentSubmission $submission): bool
    {
        return $submission->assignment?->course
            && $user->can('manageContent', $submission->assignment->course);
    }

    public function analyze(User $user, AssignmentSubmission $submission): bool
    {
        return $this->grade($user, $submission);
    }

    public function delete(User $user, AssignmentSubmission $submission): bool
    {
        return $user->isStudent()
            && (int) $submission->user_id === (int) $user->id
            && $submission->grade === null;
    }
}
