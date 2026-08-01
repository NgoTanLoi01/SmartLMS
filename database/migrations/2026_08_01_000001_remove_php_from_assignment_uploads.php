<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SAFE_DEFAULT = 'pdf,docx,txt,md,html,htm,css,js,png,jpg,jpeg';

    public function up(): void
    {
        if (! Schema::hasTable('assignments') || ! Schema::hasColumn('assignments', 'allowed_extensions')) {
            return;
        }

        DB::table('assignments')
            ->select(['id', 'allowed_extensions'])
            ->orderBy('id')
            ->chunkById(100, function ($assignments): void {
                foreach ($assignments as $assignment) {
                    $extensions = array_values(array_unique(array_filter(array_map(
                        static fn (string $extension) => strtolower(ltrim(trim($extension), '.')),
                        explode(',', (string) $assignment->allowed_extensions)
                    ))));
                    $extensions = array_values(array_filter(
                        $extensions,
                        static fn (string $extension) => $extension !== 'php'
                    ));

                    DB::table('assignments')->where('id', $assignment->id)->update([
                        'allowed_extensions' => $extensions === [] ? self::SAFE_DEFAULT : implode(',', $extensions),
                    ]);
                }
            });

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement(
                "ALTER TABLE assignments MODIFY allowed_extensions VARCHAR(255) NOT NULL DEFAULT '".self::SAFE_DEFAULT."'"
            ),
            'pgsql' => DB::statement(
                "ALTER TABLE assignments ALTER COLUMN allowed_extensions SET DEFAULT '".self::SAFE_DEFAULT."'"
            ),
            default => null,
        };
    }

    public function down(): void
    {
        // Security rollback: never add executable PHP uploads back automatically.
    }
};
