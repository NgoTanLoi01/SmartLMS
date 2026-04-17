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
    Schema::create('lesson_user', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
        $table->timestamp('completed_at')->nullable(); // Thời điểm hoàn thành
        $table->timestamps();
        
        // Đảm bảo một học sinh không bị trùng bản ghi trên cùng một bài học
        $table->unique(['user_id', 'lesson_id']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_user');
    }
};
