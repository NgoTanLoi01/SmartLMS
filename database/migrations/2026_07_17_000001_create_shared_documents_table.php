<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('folder')->nullable();
            $table->string('visibility', 20)->default('teachers');
            $table->string('disk', 40)->default('r2');
            $table->string('file_path', 1024);
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['visibility', 'created_at']);
            $table->index(['owner_id', 'folder']);
            $table->index('extension');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_documents');
    }
};
