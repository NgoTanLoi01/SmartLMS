<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['classes', 'schedules'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'status')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('status')->default('active')->after('id');
                });
            }
        }

        DB::table('classes')->whereNull('status')->update(['status' => 'active']);
        DB::table('schedules')->whereNull('status')->update(['status' => 'active']);
    }

    public function down(): void
    {
        foreach (['schedules', 'classes'] as $tableName) {
            if (Schema::hasColumn($tableName, 'status')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('status');
                });
            }
        }
    }
};
