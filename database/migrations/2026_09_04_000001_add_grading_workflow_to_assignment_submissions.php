<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table): void {
            $table->string('grading_status', 20)->default('pending')->after('feedback')->index();
            $table->json('rubric_scores')->nullable()->after('grading_status');
            $table->timestamp('grade_published_at')->nullable()->after('rubric_scores');
        });

        DB::table('assignment_submissions')
            ->whereNotNull('grade')
            ->update([
                'grading_status' => 'published',
                'grade_published_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table): void {
            $table->dropIndex(['grading_status']);
            $table->dropColumn(['grading_status', 'rubric_scores', 'grade_published_at']);
        });
    }
};
