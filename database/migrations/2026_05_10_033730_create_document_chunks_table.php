<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    // Chỉ định kết nối là pgsql (theo .env của thầy)
    protected $connection = 'pgsql';

    public function up(): void
    {
        // 1. Bật extension pgvector
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        // 2. Tạo bảng
        Schema::connection('pgsql')->create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable();
            $table->string('document_name');
            $table->text('content');
            $table->timestamps();
        });

        // 3. Thêm cột embedding kiểu vector(3072) bằng SQL thuần
        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(3072)');
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('document_chunks');
    }
};
