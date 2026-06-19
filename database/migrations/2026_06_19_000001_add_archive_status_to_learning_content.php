<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            if (!Schema::hasColumn('modules', 'status')) {
                $table->string('status')->default('published')->after('id')->index();
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'status')) {
                $table->string('status')->default('published')->after('id')->index();
            }
        });

        DB::table('modules')->whereNull('status')->update(['status' => 'published']);
        DB::table('questions')->whereNull('status')->update(['status' => 'published']);
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('modules', function (Blueprint $table) {
            if (Schema::hasColumn('modules', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
