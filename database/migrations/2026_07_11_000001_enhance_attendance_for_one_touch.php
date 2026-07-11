<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendance_columns', function (Blueprint $table) {
            $table->foreignId('schedule_id')->nullable()->after('course_id')->constrained('schedules')->nullOnDelete();
            $table->date('attendance_date')->nullable()->after('schedule_id')->index();
        });

        Schema::table('attendance_data', function (Blueprint $table) {
            $table->text('note')->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_data', function (Blueprint $table) {
            $table->dropColumn('note');
        });

        Schema::table('attendance_columns', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->dropIndex(['attendance_date']);
            $table->dropColumn(['schedule_id', 'attendance_date']);
        });
    }
};
