<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NEW_DEFAULT = 'pdf,docx,txt,md,html,htm,css,js,php,png,jpg,jpeg';

    private const OLD_DEFAULT = 'pdf,docx,zip,png,jpg,jpeg,html,htm';

    public function up(): void
    {
        DB::table('assignments')
            ->where('allowed_extensions', self::OLD_DEFAULT)
            ->update(['allowed_extensions' => self::NEW_DEFAULT]);

        DB::statement("ALTER TABLE assignments MODIFY allowed_extensions VARCHAR(255) NOT NULL DEFAULT '".self::NEW_DEFAULT."'");
    }

    public function down(): void
    {
        DB::table('assignments')
            ->where('allowed_extensions', self::NEW_DEFAULT)
            ->update(['allowed_extensions' => self::OLD_DEFAULT]);

        DB::statement("ALTER TABLE assignments MODIFY allowed_extensions VARCHAR(255) NOT NULL DEFAULT '".self::OLD_DEFAULT."'");
    }
};
