<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_user', function (Blueprint $table) {
            $table->index(['class_id', 'user_id'], 'class_user_class_user_idx');
        });

        Schema::table('class_course', function (Blueprint $table) {
            $table->index(['class_id', 'course_id'], 'class_course_class_course_idx');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->index(['course_id', 'status', 'order'], 'modules_course_status_order_idx');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->index(['module_id', 'status', 'available_from'], 'lessons_module_visibility_idx');
        });

        Schema::table('lesson_user', function (Blueprint $table) {
            $table->index(['user_id', 'completed_at'], 'lesson_user_completion_idx');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->index(['course_id', 'status', 'due_date'], 'assignments_course_status_due_idx');
        });

        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->index(['user_id', 'assignment_id'], 'assignment_submissions_user_assignment_idx');
            $table->index(['assignment_id', 'grade'], 'assignment_submissions_assignment_grade_idx');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->index(['course_id', 'status', 'available_from'], 'quizzes_course_visibility_idx');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index(['user_id', 'quiz_id', 'completed_at'], 'quiz_attempts_user_quiz_completed_idx');
        });

        Schema::table('attendance_columns', function (Blueprint $table) {
            $table->index(['course_id', 'type'], 'attendance_columns_course_type_idx');
        });

        Schema::table('attendance_data', function (Blueprint $table) {
            $table->index(['user_id', 'attendance_column_id'], 'attendance_data_user_column_idx');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['class_id', 'schedule_date', 'start_time'], 'schedules_class_date_start_idx');
            $table->index(['course_id', 'schedule_date'], 'schedules_course_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_class_date_start_idx');
            $table->dropIndex('schedules_course_date_idx');
        });
        Schema::table('attendance_data', fn (Blueprint $table) => $table->dropIndex('attendance_data_user_column_idx'));
        Schema::table('attendance_columns', fn (Blueprint $table) => $table->dropIndex('attendance_columns_course_type_idx'));
        Schema::table('quiz_attempts', fn (Blueprint $table) => $table->dropIndex('quiz_attempts_user_quiz_completed_idx'));
        Schema::table('quizzes', fn (Blueprint $table) => $table->dropIndex('quizzes_course_visibility_idx'));
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropIndex('assignment_submissions_user_assignment_idx');
            $table->dropIndex('assignment_submissions_assignment_grade_idx');
        });
        Schema::table('assignments', fn (Blueprint $table) => $table->dropIndex('assignments_course_status_due_idx'));
        Schema::table('lesson_user', fn (Blueprint $table) => $table->dropIndex('lesson_user_completion_idx'));
        Schema::table('lessons', fn (Blueprint $table) => $table->dropIndex('lessons_module_visibility_idx'));
        Schema::table('modules', fn (Blueprint $table) => $table->dropIndex('modules_course_status_order_idx'));
        Schema::table('class_course', fn (Blueprint $table) => $table->dropIndex('class_course_class_course_idx'));
        Schema::table('class_user', fn (Blueprint $table) => $table->dropIndex('class_user_class_user_idx'));
    }
};
