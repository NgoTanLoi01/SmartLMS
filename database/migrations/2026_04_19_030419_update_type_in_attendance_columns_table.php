<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_columns', function (Blueprint $table) {
            // Thêm 'note' vào danh sách enum cho phép
            $table->enum('type', ['attendance', 'grade', 'note'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('attendance_columns')->where('type', 'note')->update(['type' => 'attendance']);

        Schema::table('attendance_columns', function (Blueprint $table) {
            $table->enum('type', ['attendance', 'grade'])->change();
        });
    }
};
