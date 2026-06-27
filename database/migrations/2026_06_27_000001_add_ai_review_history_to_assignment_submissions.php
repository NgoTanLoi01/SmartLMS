<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->json('ai_review_flags')->nullable()->after('ai_rubric_breakdown');
            $table->json('ai_analysis_history')->nullable()->after('ai_analyzed_at');
        });
    }

    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'ai_review_flags',
                'ai_analysis_history',
            ]);
        });
    }
};
