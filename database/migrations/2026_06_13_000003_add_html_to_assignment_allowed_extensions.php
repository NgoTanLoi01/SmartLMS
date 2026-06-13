<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assignments')
            ->where('allowed_extensions', 'pdf,docx,zip,png,jpg,jpeg')
            ->update(['allowed_extensions' => 'pdf,docx,zip,png,jpg,jpeg,html,htm']);

        if (Schema::hasTable('assignments')) {
            DB::statement("ALTER TABLE assignments MODIFY allowed_extensions VARCHAR(255) NOT NULL DEFAULT 'pdf,docx,zip,png,jpg,jpeg,html,htm'");
        }
    }

    public function down(): void
    {
        DB::table('assignments')
            ->where('allowed_extensions', 'pdf,docx,zip,png,jpg,jpeg,html,htm')
            ->update(['allowed_extensions' => 'pdf,docx,zip,png,jpg,jpeg']);

        if (Schema::hasTable('assignments')) {
            DB::statement("ALTER TABLE assignments MODIFY allowed_extensions VARCHAR(255) NOT NULL DEFAULT 'pdf,docx,zip,png,jpg,jpeg'");
        }
    }
};
