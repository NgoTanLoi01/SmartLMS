<?php

namespace App\Application\Identity;

use App\Models\User;
use App\Services\AuditLogger;
use App\Support\StudentLoginCode;
use Illuminate\Support\Facades\DB;

class UpdateUserProfile
{
    /** @param array<string, string|null> $data */
    public function handle(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $trackedFields = ['name', 'email', 'username', 'student_code', 'role'];
            $oldValues = AuditLogger::snapshot($lockedUser, $trackedFields);

            $attributes = ['name' => $data['name']];
            if ($lockedUser->isStudent()) {
                $attributes['username'] = $data['username'];
                $attributes['student_code'] = $data['student_code'];
                $attributes['email'] = $data['email'] ?: StudentLoginCode::emailFromUsername($data['username']);
            } else {
                $attributes['email'] = $data['email'];
            }

            $lockedUser->forceFill($attributes)->save();

            AuditLogger::log(
                AuditLogger::ACCOUNT_PROFILE_UPDATED,
                $lockedUser,
                $oldValues,
                AuditLogger::snapshot($lockedUser, $trackedFields),
                description: "Cập nhật thông tin tài khoản {$lockedUser->name}"
            );

            return $lockedUser;
        });
    }
}
