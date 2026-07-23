<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng định nghĩa các cột trong bảng điểm danh/điểm số
        Schema::create('attendance_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Tên cột: B1, B2, HS1...
            $table->enum('type', ['attendance', 'grade']); // Loại cột
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Bảng lưu giá trị thực tế (v, điểm số, ghi chú)
        Schema::create('attendance_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_column_id')->constrained('attendance_columns')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('value')->nullable(); // Lưu "v" hoặc điểm số
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_data');
        Schema::dropIfExists('attendance_columns');
    }
};
