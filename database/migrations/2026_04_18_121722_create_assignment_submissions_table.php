<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            // Liên kết tới bảng assignments (Khóa ngoại)
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');

            // Liên kết tới bảng users (Học sinh nộp bài)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->string('file_path'); // Đường dẫn lưu file
            $table->decimal('grade', 5, 2)->nullable(); // Điểm số
            $table->text('feedback')->nullable(); // Lời phê của GV
            $table->timestamp('submitted_at')->nullable(); // Giờ nộp bài
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
