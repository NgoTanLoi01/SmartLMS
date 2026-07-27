<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('observed_difficulty')->nullable()->after('difficulty');
            $table->json('difficulty_metrics')->nullable()->after('observed_difficulty');
            $table->timestamp('difficulty_evaluated_at')->nullable()->after('difficulty_metrics');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['observed_difficulty', 'difficulty_metrics', 'difficulty_evaluated_at']);
        });
    }
};
