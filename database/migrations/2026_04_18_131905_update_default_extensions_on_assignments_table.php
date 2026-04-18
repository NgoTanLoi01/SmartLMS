<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('allowed_extensions')->default('pdf,docx,zip,png,jpg,jpeg')->change();
        });

        // Thêm dòng này để cập nhật dữ liệu hiện có ngay khi migrate
        \DB::table('assignments')
            ->where('allowed_extensions', 'pdf,docx,zip')
            ->update(['allowed_extensions' => 'pdf,docx,zip,png,jpg,jpeg']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
