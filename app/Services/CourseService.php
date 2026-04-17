<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;

class CourseService 
{
    /**
     * Đăng ký khóa học cho học sinh
     */
    public function enrollStudent($courseId, $studentId)
    {
        $course = Course::findOrFail($courseId);
        $student = User::findOrFail($studentId);

        // Kiểm tra xem học sinh đã đăng ký chưa
        if ($course->students()->where('user_id', $studentId)->exists()) {
            return ['status' => 'error', 'message' => 'Bạn đã tham gia khóa học này rồi.'];
        }

        // Thực hiện đính kèm (attach) vào bảng trung gian enrollments
        $course->students()->attach($studentId);

        return ['status' => 'success', 'message' => 'Đăng ký thành công!'];
    }
}