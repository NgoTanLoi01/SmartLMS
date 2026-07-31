<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('quizzes', 'max_attempts')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->unsignedTinyInteger('max_attempts')->default(1)->after('time_limit');
            });
        }

        if (! Schema::hasColumn('quiz_attempts', 'attempt_number')) {
            // MySQL có thể dùng unique cũ làm index đỡ FK quiz_id. Tạo index riêng trước khi bỏ unique.
            Schema::table('quiz_attempts', function (Blueprint $table) {
                $table->index('quiz_id', 'quiz_attempts_quiz_id_index');
            });
            Schema::table('quiz_attempts', function (Blueprint $table) {
                $table->dropUnique('quiz_attempts_quiz_user_unique');
                $table->unsignedSmallInteger('attempt_number')->default(1)->after('user_id');
                $table->unique(['quiz_id', 'user_id', 'attempt_number'], 'quiz_attempts_quiz_user_number_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropUnique('quiz_attempts_quiz_user_number_unique');
            $table->dropColumn('attempt_number');
        });

        // Chỉ khôi phục ràng buộc cũ khi dữ liệu vẫn đáp ứng quy tắc một lượt.
        // Nếu đã phát sinh nhiều lượt, rollback vẫn giữ dữ liệu thay vì âm thầm xóa bài làm.
        $hasMultipleAttempts = DB::table('quiz_attempts')
            ->select(['quiz_id', 'user_id'])
            ->groupBy('quiz_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $hasMultipleAttempts) {
            Schema::table('quiz_attempts', function (Blueprint $table) {
                $table->unique(['quiz_id', 'user_id'], 'quiz_attempts_quiz_user_unique');
                $table->dropIndex('quiz_attempts_quiz_id_index');
            });
        }

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('max_attempts');
        });
    }
};
