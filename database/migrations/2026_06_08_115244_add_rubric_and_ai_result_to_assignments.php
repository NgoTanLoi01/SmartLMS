<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->longText('grading_rubric')->nullable()->after('instructions');
            $table->unsignedTinyInteger('grading_scale')->default(10)->after('grading_rubric');
            $table->boolean('ai_grading_enabled')->default(true)->after('grading_scale');
        });

        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->decimal('ai_suggested_score', 5, 2)->nullable()->after('feedback');
            $table->longText('ai_feedback')->nullable()->after('ai_suggested_score');
            $table->json('ai_rubric_breakdown')->nullable()->after('ai_feedback');
            $table->longText('ai_grading_notes')->nullable()->after('ai_rubric_breakdown');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_grading_notes');
        });
    }

    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'ai_suggested_score',
                'ai_feedback',
                'ai_rubric_breakdown',
                'ai_grading_notes',
                'ai_analyzed_at',
            ]);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn([
                'grading_rubric',
                'grading_scale',
                'ai_grading_enabled',
            ]);
        });
    }
};
