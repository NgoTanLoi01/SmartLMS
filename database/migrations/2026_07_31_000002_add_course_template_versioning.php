<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedInteger('template_version')->default(1)->after('course_type');
            $table->foreignId('source_template_id')->nullable()->after('template_version')->constrained('courses')->nullOnDelete();
            $table->unsignedInteger('synced_template_version')->nullable()->after('source_template_id');
            $table->json('template_section_versions')->nullable()->after('synced_template_version');
            $table->json('template_sync_state')->nullable()->after('synced_template_version');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->foreignId('template_origin_id')->nullable()->after('course_id')->constrained('modules')->nullOnDelete();
            $table->unique(['course_id', 'template_origin_id'], 'modules_course_template_origin_unique');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('template_origin_id')->nullable()->after('module_id')->constrained('lessons')->nullOnDelete();
            $table->unique(['module_id', 'template_origin_id'], 'lessons_module_template_origin_unique');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->foreignId('template_origin_id')->nullable()->after('course_id')->constrained('assignments')->nullOnDelete();
            $table->unique(['course_id', 'template_origin_id'], 'assignments_course_template_origin_unique');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('template_origin_id')->nullable()->after('course_id')->constrained('quizzes')->nullOnDelete();
            $table->unique(['course_id', 'template_origin_id'], 'quizzes_course_template_origin_unique');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('template_origin_id')->nullable()->after('course_id')->constrained('questions')->nullOnDelete();
            $table->unique(['course_id', 'template_origin_id'], 'questions_course_template_origin_unique');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique('questions_course_template_origin_unique');
            $table->dropConstrainedForeignId('template_origin_id');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropUnique('quizzes_course_template_origin_unique');
            $table->dropConstrainedForeignId('template_origin_id');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropUnique('assignments_course_template_origin_unique');
            $table->dropConstrainedForeignId('template_origin_id');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropUnique('lessons_module_template_origin_unique');
            $table->dropConstrainedForeignId('template_origin_id');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropUnique('modules_course_template_origin_unique');
            $table->dropConstrainedForeignId('template_origin_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_template_id');
            $table->dropColumn(['template_version', 'synced_template_version', 'template_section_versions', 'template_sync_state']);
        });
    }
};
