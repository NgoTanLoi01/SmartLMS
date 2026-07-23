<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            // Đổi tên hoặc thêm cột để xác định thời điểm nộp thực tế
            $table->timestamp('submitted_at')->nullable()->after('file_path');

            // Thêm cột để biết bản nộp này là bản nháp hay bản chính thức
            $table->boolean('is_final')->default(true)->after('submitted_at');

            // Tối ưu tốc độ truy vấn khi giáo viên xem danh sách bài nộp của 1 bài tập
            $table->index(['assignment_id', 'student_id']);

            // SoftDeletes để lưu vết nếu học sinh muốn nộp lại bản mới
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['assignment_id', 'student_id']);
            $table->dropColumn(['submitted_at', 'is_final', 'deleted_at']);
        });
    }
};
