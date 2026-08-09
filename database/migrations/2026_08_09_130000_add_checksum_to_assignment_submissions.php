<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assignment_submissions')
            && ! Schema::hasColumn('assignment_submissions', 'checksum_sha256')) {
            Schema::table('assignment_submissions', function (Blueprint $table): void {
                $table->char('checksum_sha256', 64)->nullable()->after('file_size');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assignment_submissions')
            && Schema::hasColumn('assignment_submissions', 'checksum_sha256')) {
            Schema::table('assignment_submissions', function (Blueprint $table): void {
                $table->dropColumn('checksum_sha256');
            });
        }
    }
};
