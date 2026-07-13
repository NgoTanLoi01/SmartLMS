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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content')->nullable(); // Dùng cho CKEditor/TinyMCE
            $table->string('video_url')->nullable(); // Nhúng YouTube/Vimeo
            $table->string('attachment_path')->nullable(); // Đường dẫn file PDF/Tài liệu
            $table->integer('order')->default(0); // Thứ tự bài học trong chương
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
