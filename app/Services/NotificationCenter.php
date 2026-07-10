<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\SmartNotification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationCenter
{
    public function notifyUser(User|int $user, string $type, string $title, string $message, ?string $actionUrl = null, array $data = [], ?string $dedupeKey = null): void
    {
        $this->notifyUserIds(collect([$user instanceof User ? $user->id : $user]), $type, $title, $message, $actionUrl, $data, $dedupeKey);
    }

    public function notifyCourseStudents(Course|int $course, string $type, string $title, string $message, ?string $actionUrl = null, array $data = [], ?string $dedupeKey = null, ?int $classId = null): void
    {
        $courseId = $course instanceof Course ? $course->id : $course;
        $studentIds = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereHas('classes', function ($query) use ($courseId, $classId) {
                $query->where('classes.status', Classroom::STATUS_ACTIVE)
                    ->whereHas('courses', fn ($courseQuery) => $courseQuery->where('courses.id', $courseId))
                    ->when($classId, fn ($classQuery) => $classQuery->where('classes.id', $classId));
            })
            ->pluck('users.id');

        $this->notifyUserIds($studentIds, $type, $title, $message, $actionUrl, $data + ['course_id' => $courseId], $dedupeKey);
    }

    public function notifyClassStudents(int $classId, string $type, string $title, string $message, ?string $actionUrl = null, array $data = [], ?string $dedupeKey = null): void
    {
        $studentIds = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereHas('classes', fn ($query) => $query->where('classes.id', $classId)->where('classes.status', Classroom::STATUS_ACTIVE))
            ->pluck('users.id');

        $this->notifyUserIds($studentIds, $type, $title, $message, $actionUrl, $data + ['class_id' => $classId], $dedupeKey);
    }

    private function notifyUserIds(Collection $userIds, string $type, string $title, string $message, ?string $actionUrl, array $data, ?string $dedupeKey): void
    {
        $now = now();
        $rows = $userIds->unique()->map(fn ($userId) => [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'data' => $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
            'dedupe_key' => $dedupeKey,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows) {
            DB::table((new SmartNotification())->getTable())->insertOrIgnore($rows);
        }
    }
}
