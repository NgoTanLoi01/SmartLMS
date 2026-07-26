<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_passages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->string('source_label')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'title']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('quiz_passage_id')->nullable()->after('question_bank_id')->constrained('quiz_passages')->nullOnDelete();
        });

        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('scheduled');
            $table->string('result_release_policy')->default('after_session');
            $table->timestamp('results_released_at')->nullable();
            $table->timestamps();

            $table->index(['quiz_id', 'starts_at', 'ends_at']);
            $table->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('quiz_session_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('extra_time_minutes')->default(0);
            $table->timestamps();

            $table->unique(['quiz_session_id', 'user_id']);
            $table->index(['user_id', 'quiz_session_id']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('quiz_session_id')->nullable()->after('quiz_id')->constrained()->nullOnDelete();
            $table->string('status')->default('submitted')->after('user_id');
            $table->timestamp('expires_at')->nullable()->after('started_at');
            $table->timestamp('last_seen_at')->nullable()->after('expires_at');
            $table->unsignedInteger('current_position')->default(1)->after('last_seen_at');
            $table->json('flagged_question_ids')->nullable()->after('current_position');
            $table->timestamp('result_released_at')->nullable()->after('completed_at');

            $table->index(['quiz_session_id', 'status']);
            $table->index(['status', 'last_seen_at']);
        });

        DB::table('quiz_attempts')->whereNotNull('completed_at')->update(['status' => 'submitted']);

        Schema::create('quiz_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('position');
            $table->text('question_text');
            $table->string('passage_title')->nullable();
            $table->longText('passage_content')->nullable();
            $table->string('passage_source_label')->nullable();
            $table->json('option_snapshot');
            $table->unsignedBigInteger('correct_option_id');
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'position']);
            $table->index(['quiz_attempt_id', 'question_id']);
        });

        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_attempt_question_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('selected_option_id')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'quiz_attempt_question_id'], 'quiz_attempt_answer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
        Schema::dropIfExists('quiz_attempt_questions');

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropForeign(['quiz_session_id']);
            $table->dropIndex(['quiz_session_id', 'status']);
            $table->dropIndex(['status', 'last_seen_at']);
            $table->dropColumn([
                'quiz_session_id',
                'status',
                'expires_at',
                'last_seen_at',
                'current_position',
                'flagged_question_ids',
                'result_released_at',
            ]);
        });

        Schema::dropIfExists('quiz_session_user');
        Schema::dropIfExists('quiz_sessions');

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['quiz_passage_id']);
            $table->dropColumn('quiz_passage_id');
        });
        Schema::dropIfExists('quiz_passages');
    }
};
