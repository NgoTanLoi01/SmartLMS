<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function update(User $user, Question $question): bool
    {
        if ($question->questionBank && $user->can('update', $question->questionBank)) {
            return true;
        }

        return $question->course && $user->can('manageContent', $question->course);
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->update($user, $question);
    }
}
