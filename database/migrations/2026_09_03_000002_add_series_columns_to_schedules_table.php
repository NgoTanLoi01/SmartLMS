<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->uuid('series_id')->nullable()->after('id');
            $table->unsignedSmallInteger('series_position')->nullable()->after('series_id');
            $table->index(['series_id', 'status'], 'schedules_series_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_series_status_idx');
            $table->dropColumn(['series_id', 'series_position']);
        });
    }
};
