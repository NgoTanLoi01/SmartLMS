<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cập nhật bảng questions (Ngân hàng câu hỏi)
        Schema::table('questions', function (Blueprint $table) {
            // Xóa khóa ngoại quiz_id cũ
            $table->dropForeign(['quiz_id']);
            $table->dropColumn('quiz_id');

            // Thêm course_id và độ khó
            $table->foreignId('course_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table
                ->enum('difficulty', ['easy', 'medium', 'hard'])
                ->default('medium')
                ->after('question_text');
        });

        // 2. Cập nhật bảng quizzes (Cấu hình trộn đề)
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('is_random')->default(true)->after('time_limit');
            $table->integer('easy_count')->default(0)->after('is_random');
            $table->integer('medium_count')->default(0)->after('easy_count');
            $table->integer('hard_count')->default(0)->after('medium_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
