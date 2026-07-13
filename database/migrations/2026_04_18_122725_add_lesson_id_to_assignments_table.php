<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Thêm lesson_id và cho phép null (phòng trường hợp bài tập chung của cả khóa)
            $table->foreignId('lesson_id')->nullable()->after('course_id')->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->dropColumn('lesson_id');
        });
    }
};
