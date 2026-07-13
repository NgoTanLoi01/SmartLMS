<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->string('file_disk')->default('public')->after('file_path');
            $table->string('original_filename')->nullable()->after('file_disk');
            $table->string('mime_type')->nullable()->after('original_filename');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropColumn(['file_disk', 'original_filename', 'mime_type', 'file_size']);
        });
    }
};
