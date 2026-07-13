<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->string('storage_status', 30)->nullable()->after('status');
            $table->timestamp('last_verified_at')->nullable()->after('storage_status');
            $table->timestamp('imported_at')->nullable()->after('last_verified_at');
            $table->unique(['disk', 'file_path'], 'learning_materials_disk_path_unique');
        });

        Schema::create('learning_material_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->timestamps();

            $table->unique(
                ['learning_material_id', 'source_type', 'source_id'],
                'learning_material_sources_reference_unique'
            );
            $table->index(['course_id', 'source_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_material_sources');

        Schema::table('learning_materials', function (Blueprint $table) {
            $table->dropUnique('learning_materials_disk_path_unique');
            $table->dropColumn(['storage_status', 'last_verified_at', 'imported_at']);
        });
    }
};
