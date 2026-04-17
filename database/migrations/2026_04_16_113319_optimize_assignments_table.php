<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('assignments', function (Blueprint $table) {
        $table->string('allowed_extensions')->default('pdf,docx,zip')->after('due_date');
        $table->integer('max_file_size')->default(5120)->after('allowed_extensions');
        $table->enum('status', ['draft', 'published'])->default('published')->after('max_file_size');
        $table->softDeletes();
    });
}

public function down(): void
{
    Schema::table('assignments', function (Blueprint $table) {
        $table->dropColumn(['allowed_extensions', 'max_file_size', 'status', 'deleted_at']);
    });
}

};
