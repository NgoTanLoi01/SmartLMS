<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_material_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->foreignId('unlock_when_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->timestamp('available_from')->nullable();
            $table->string('status', 30)->default('published');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'status']);
            $table->index(['lesson_id', 'status']);
            $table->index(['class_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_material_assignments');
    }
};
