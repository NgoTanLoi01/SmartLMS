<?php

namespace App\Policies;

use App\Models\QuestionBank;
use App\Models\User;

class QuestionBankPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, QuestionBank $bank): bool
    {
        if ($user->isAdmin() || ($user->isTeacher() && (int) $bank->teacher_id === (int) $user->id)) {
            return true;
        }

        return $user->isTeacher()
            && $bank->courses()->where('teacher_id', $user->id)->exists();
    }

    public function delete(User $user, QuestionBank $bank): bool
    {
        return $this->update($user, $bank);
    }
}
