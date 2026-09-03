<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_DEFAULT_KB = 5120;

    private const NEW_DEFAULT_KB = 20480;

    public function up(): void
    {
        DB::table('assignments')
            ->whereNull('max_file_size')
            ->orWhere('max_file_size', self::OLD_DEFAULT_KB)
            ->update(['max_file_size' => self::NEW_DEFAULT_KB]);

        DB::statement('ALTER TABLE assignments MODIFY max_file_size INT NOT NULL DEFAULT '.self::NEW_DEFAULT_KB);
    }

    public function down(): void
    {
        DB::table('assignments')
            ->where('max_file_size', self::NEW_DEFAULT_KB)
            ->update(['max_file_size' => self::OLD_DEFAULT_KB]);

        DB::statement('ALTER TABLE assignments MODIFY max_file_size INT NOT NULL DEFAULT '.self::OLD_DEFAULT_KB);
    }
};
