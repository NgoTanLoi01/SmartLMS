<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('type')->default('file')->after('lesson_id');
        });

        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->longText('text_answer')->nullable()->after('file_path');
        });

        DB::statement('ALTER TABLE assignment_submissions MODIFY file_path VARCHAR(255) NULL');
    }

    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropColumn('text_answer');
        });

        DB::statement("UPDATE assignment_submissions SET file_path = '' WHERE file_path IS NULL");
        DB::statement('ALTER TABLE assignment_submissions MODIFY file_path VARCHAR(255) NOT NULL');

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
