<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('question_type')->default('single_choice')->after('quiz_passage_id');
            $table->json('answer_config')->nullable()->after('question_text');
            $table->index(['question_type', 'difficulty']);
        });

        Schema::table('quiz_attempt_questions', function (Blueprint $table) {
            $table->string('question_type')->default('single_choice')->after('question_id');
            $table->json('answer_key_snapshot')->nullable()->after('option_snapshot');
            $table->json('response_schema_snapshot')->nullable()->after('answer_key_snapshot');
        });

        Schema::table('quiz_attempt_answers', function (Blueprint $table) {
            $table->json('answer_payload')->nullable()->after('selected_option_id');
            $table->boolean('is_correct')->nullable()->after('answer_payload');
        });

        DB::table('quiz_attempt_questions')->orderBy('id')->each(function ($question) {
            DB::table('quiz_attempt_questions')->where('id', $question->id)->update([
                'answer_key_snapshot' => json_encode([
                    'option_ids' => [(int) $question->correct_option_id],
                ]),
            ]);
        });

        DB::table('quiz_attempt_answers')->whereNotNull('selected_option_id')->orderBy('id')->each(function ($answer) {
            DB::table('quiz_attempt_answers')->where('id', $answer->id)->update([
                'answer_payload' => json_encode((int) $answer->selected_option_id),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempt_answers', function (Blueprint $table) {
            $table->dropColumn(['answer_payload', 'is_correct']);
        });

        Schema::table('quiz_attempt_questions', function (Blueprint $table) {
            $table->dropColumn(['question_type', 'answer_key_snapshot', 'response_schema_snapshot']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['question_type', 'difficulty']);
            $table->dropColumn(['question_type', 'answer_config']);
        });
    }
};
