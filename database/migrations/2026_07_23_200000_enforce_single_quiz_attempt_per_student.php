<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateGroups = DB::table('quiz_attempts')
            ->select(['quiz_id', 'user_id'])
            ->groupBy('quiz_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $attemptIds = DB::table('quiz_attempts')
                ->where('quiz_id', $group->quiz_id)
                ->where('user_id', $group->user_id)
                ->orderByRaw('CASE WHEN completed_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('completed_at')
                ->orderBy('id')
                ->pluck('id');

            DB::table('quiz_attempts')->whereIn('id', $attemptIds->slice(1))->delete();
        }

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->unique(['quiz_id', 'user_id'], 'quiz_attempts_quiz_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropUnique('quiz_attempts_quiz_user_unique');
        });
    }
};
