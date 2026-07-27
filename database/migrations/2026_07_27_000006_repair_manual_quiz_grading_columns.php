<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quiz_attempt_questions')) {
            if (! Schema::hasColumn('quiz_attempt_questions', 'grading_mode')) {
                Schema::table('quiz_attempt_questions', function (Blueprint $table) {
                    $table->string('grading_mode')->default('auto')->after('question_type');
                });
            }

            if (! Schema::hasColumn('quiz_attempt_questions', 'max_score')) {
                Schema::table('quiz_attempt_questions', function (Blueprint $table) {
                    $table->decimal('max_score', 6, 2)->default(1)->after('grading_mode');
                });
            }
        }

        if (Schema::hasTable('quiz_attempt_answers')) {
            if (! Schema::hasColumn('quiz_attempt_answers', 'grading_status')) {
                Schema::table('quiz_attempt_answers', function (Blueprint $table) {
                    $table->string('grading_status')->default('ungraded')->after('is_correct');
                });
            }

            if (! Schema::hasColumn('quiz_attempt_answers', 'score')) {
                Schema::table('quiz_attempt_answers', function (Blueprint $table) {
                    $table->decimal('score', 6, 2)->nullable()->after('grading_status');
                });
            }

            if (! Schema::hasColumn('quiz_attempt_answers', 'rubric_scores')) {
                Schema::table('quiz_attempt_answers', function (Blueprint $table) {
                    $table->json('rubric_scores')->nullable()->after('score');
                });
            }

            if (! Schema::hasColumn('quiz_attempt_answers', 'teacher_feedback')) {
                Schema::table('quiz_attempt_answers', function (Blueprint $table) {
                    $table->text('teacher_feedback')->nullable()->after('rubric_scores');
                });
            }

            if (! Schema::hasColumn('quiz_attempt_answers', 'graded_by')) {
                Schema::table('quiz_attempt_answers', function (Blueprint $table) {
                    $table->foreignId('graded_by')
                        ->nullable()
                        ->after('teacher_feedback')
                        ->constrained('users')
                        ->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('quiz_attempt_answers', 'graded_at')) {
                Schema::table('quiz_attempt_answers', function (Blueprint $table) {
                    $table->timestamp('graded_at')->nullable()->after('graded_by');
                });
            }
        }
    }

    public function down(): void
    {
        // Migration sửa chữa dữ liệu vận hành: không tự động gỡ các cột ổn định
        // vì chúng có thể đã tồn tại trước khi migration này được chạy.
    }
};
