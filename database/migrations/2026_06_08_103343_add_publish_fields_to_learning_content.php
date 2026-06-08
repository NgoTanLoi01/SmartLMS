<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['courses', 'lessons', 'quizzes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('status')->default('published')->after('id');
                $table->timestamp('published_at')->nullable()->after('status');
                $table->timestamp('available_from')->nullable()->after('published_at');
            });
        }

        DB::statement("ALTER TABLE assignments MODIFY status VARCHAR(255) NOT NULL DEFAULT 'published'");
        Schema::table('assignments', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('status');
            $table->timestamp('available_from')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        foreach (['courses', 'lessons', 'quizzes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['status', 'published_at', 'available_from']);
            });
        }

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['published_at', 'available_from']);
        });
        DB::statement("ALTER TABLE assignments MODIFY status ENUM('draft', 'published') NOT NULL DEFAULT 'published'");
    }
};
