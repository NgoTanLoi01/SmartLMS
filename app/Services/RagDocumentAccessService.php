<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;

class RagDocumentAccessService
{
    /**
     * @return array<int, int>
     */
    public function accessibleCourseIds(User $user): array
    {
        $courses = Course::query()->notArchived();

        if ($user->isAdmin()) {
            return $this->integerIds($courses->pluck('id')->all());
        }

        if ($user->isTeacher()) {
            return $this->integerIds(
                $courses->where('teacher_id', $user->id)->pluck('id')->all()
            );
        }

        if (! $user->isStudent()) {
            return [];
        }

        $classIds = $user->classes()
            ->where('classes.status', Classroom::STATUS_ACTIVE)
            ->pluck('classes.id');

        if ($classIds->isEmpty()) {
            return [];
        }

        return $this->integerIds(
            $courses->visibleToStudents()
                ->whereHas('classes', fn ($query) => $query->whereIn('classes.id', $classIds))
                ->pluck('id')
                ->all()
        );
    }

    /**
     * Áp dụng phạm vi dùng cho chatbot. Tài liệu toàn hệ thống chỉ được đưa
     * vào RAG cho các vai trò hợp lệ; nếu có khóa học hiện tại thì chỉ lấy
     * tài liệu global và tài liệu đúng khóa học đó.
     */
    public function scopeForRetrieval($query, User $user, ?int $courseId = null, ?array $accessibleCourseIds = null)
    {
        if (! $user->hasRole(User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_STUDENT)) {
            return $query->whereRaw('1 = 0');
        }

        $courseIds = $accessibleCourseIds ?? $this->accessibleCourseIds($user);

        if ($courseId !== null) {
            if (! in_array($courseId, $courseIds, true)) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where(function ($scope) use ($courseId) {
                $scope->whereNull('course_id')
                    ->orWhere('course_id', 0)
                    ->orWhere('course_id', $courseId);
            });
        }

        return $query->where(function ($scope) use ($courseIds) {
            $scope->whereNull('course_id')->orWhere('course_id', 0);
            if ($courseIds !== []) {
                $scope->orWhereIn('course_id', $courseIds);
            }
        });
    }

    /**
     * Trang quản lý chỉ hiển thị tài liệu thuộc khóa giáo viên phụ trách.
     * Tài liệu global và tài liệu khóa khác chỉ quản trị viên được nhìn thấy.
     */
    public function scopeForManagement($query, User $user, ?array $accessibleCourseIds = null)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if (! $user->isTeacher()) {
            return $query->whereRaw('1 = 0');
        }

        $courseIds = $accessibleCourseIds ?? $this->accessibleCourseIds($user);

        return $courseIds === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('course_id', $courseIds);
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, int>
     */
    private function integerIds(array $ids): array
    {
        return array_values(array_map(static fn ($id) => (int) $id, $ids));
    }
}
