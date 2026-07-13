<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseContentSeeder extends Seeder
{
    public function run(): void
    {
        // // 1. Tìm một giáo viên đã có (hoặc tạo mới nếu chưa có)
        // $teacher = User::where('role', 'teacher')->first() ?? User::factory()->create(['role' => 'teacher']);

        // // 2. Tạo một khóa học mẫu
        // $course = Course::create([
        //     'title' => 'Lập trình Laravel từ cơ bản đến nâng cao',
        //     'description' => 'Khóa học giúp bạn làm chủ Framework Laravel trong 30 ngày.',
        //     'teacher_id' => $teacher->id,
        // ]);

        // // 3. Tạo Module 1
        // $module1 = Module::create([
        //     'course_id' => $course->id,
        //     'title' => 'Chương 1: Cài đặt và Cấu trúc dự án',
        //     'order' => 1,
        // ]);

        // // Tạo bài học cho Module 1
        // Lesson::create([
        //     'module_id' => $module1->id,
        //     'title' => 'Bài 1: Cài đặt môi trường Docker',
        //     'content' => 'Hướng dẫn cài đặt Docker và Docker Compose...',
        //     'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        //     'order' => 1,
        // ]);

        // Lesson::create([
        //     'module_id' => $module1->id,
        //     'title' => 'Bài 2: Cấu trúc thư mục Laravel',
        //     'content' => 'Tìm hiểu về thư mục app, routes, resources...',
        //     'order' => 2,
        // ]);

        // // 4. Tạo Module 2
        // $module2 = Module::create([
        //     'course_id' => $course->id,
        //     'title' => 'Chương 2: Làm việc với Database',
        //     'order' => 2,
        // ]);

        // Lesson::create([
        //     'module_id' => $module2->id,
        //     'title' => 'Bài 1: Migration và Seeder là gì?',
        //     'content' => 'Cách tạo bảng và đổ dữ liệu mẫu...',
        //     'order' => 1,
        // ]);
    }
}
