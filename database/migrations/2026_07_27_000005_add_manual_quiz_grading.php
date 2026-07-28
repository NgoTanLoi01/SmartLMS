<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Một số bản cài đặt cũ đã ghi nhận migration nền tảng nhưng thiếu các bảng con.
        // Tạo bù theo cách idempotent để nâng cấp không làm mất bài thi hiện có.
        if (! Schema::hasTable('quiz_session_user')) {
            Schema::create('quiz_session_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_session_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('extra_time_minutes')->default(0);
                $table->timestamps();
                $table->unique(['quiz_session_id', 'user_id']);
                $table->index(['user_id', 'quiz_session_id']);
            });
        }

        if (! Schema::hasTable('quiz_attempt_questions')) {
            Schema::create('quiz_attempt_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
                $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
                $table->string('question_type')->default('single_choice');
                $table->string('grading_mode')->default('auto');
                $table->decimal('max_score', 6, 2)->default(1);
                $table->unsignedInteger('position');
                $table->text('question_text');
                $table->string('passage_title')->nullable();
                $table->longText('passage_content')->nullable();
                $table->string('passage_source_label')->nullable();
                $table->json('option_snapshot');
                $table->json('answer_key_snapshot')->nullable();
                $table->json('response_schema_snapshot')->nullable();
                $table->unsignedBigInteger('correct_option_id')->default(0);
                $table->timestamps();
                $table->unique(['quiz_attempt_id', 'position']);
                $table->index(['quiz_attempt_id', 'question_id']);
            });
        } else {
            if (! Schema::hasColumn('quiz_attempt_questions', 'grading_mode')) {
                Schema::table('quiz_attempt_questions', fn (Blueprint $table) => $table->string('grading_mode')->default('auto')->after('question_type'));
            }
            if (! Schema::hasColumn('quiz_attempt_questions', 'max_score')) {
                Schema::table('quiz_attempt_questions', fn (Blueprint $table) => $table->decimal('max_score', 6, 2)->default(1)->after('grading_mode'));
            }
        }

        if (! Schema::hasTable('quiz_attempt_answers')) {
            Schema::create('quiz_attempt_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
                $table->foreignId('quiz_attempt_question_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('selected_option_id')->nullable();
                $table->json('answer_payload')->nullable();
                $table->boolean('is_correct')->nullable();
                $table->string('grading_status')->default('ungraded');
                $table->decimal('score', 6, 2)->nullable();
                $table->json('rubric_scores')->nullable();
                $table->text('teacher_feedback')->nullable();
                $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('graded_at')->nullable();
                $table->timestamp('answered_at')->nullable();
                $table->timestamps();
                $table->unique(['quiz_attempt_id', 'quiz_attempt_question_id'], 'quiz_attempt_answer_unique');
                $table->index(['grading_status', 'graded_at']);
            });
        } else {
            if (! Schema::hasColumn('quiz_attempt_answers', 'grading_status')) {
                Schema::table('quiz_attempt_answers', fn (Blueprint $table) => $table->string('grading_status')->default('ungraded')->after('is_correct'));
            }
            if (! Schema::hasColumn('quiz_attempt_answers', 'score')) {
                Schema::table('quiz_attempt_answers', fn (Blueprint $table) => $table->decimal('score', 6, 2)->nullable()->after('grading_status'));
            }
            if (! Schema::hasColumn('quiz_attempt_answers', 'rubric_scores')) {
                Schema::table('quiz_attempt_answers', fn (Blueprint $table) => $table->json('rubric_scores')->nullable()->after('score'));
            }
            if (! Schema::hasColumn('quiz_attempt_answers', 'teacher_feedback')) {
                Schema::table('quiz_attempt_answers', fn (Blueprint $table) => $table->text('teacher_feedback')->nullable()->after('rubric_scores'));
            }
            if (! Schema::hasColumn('quiz_attempt_answers', 'graded_by')) {
                Schema::table('quiz_attempt_answers', fn (Blueprint $table) => $table->foreignId('graded_by')->nullable()->after('teacher_feedback')->constrained('users')->nullOnDelete());
            }
            if (! Schema::hasColumn('quiz_attempt_answers', 'graded_at')) {
                Schema::table('quiz_attempt_answers', fn (Blueprint $table) => $table->timestamp('graded_at')->nullable()->after('graded_by'));
            }
        }

        if (! Schema::hasColumn('quiz_attempts', 'auto_score')) {
            Schema::table('quiz_attempts', fn (Blueprint $table) => $table->decimal('auto_score', 5, 2)->nullable()->after('score'));
        }
        if (! Schema::hasColumn('quiz_attempts', 'manual_score')) {
            Schema::table('quiz_attempts', fn (Blueprint $table) => $table->decimal('manual_score', 5, 2)->nullable()->after('auto_score'));
        }
        if (! Schema::hasColumn('quiz_attempts', 'graded_at')) {
            Schema::table('quiz_attempts', fn (Blueprint $table) => $table->timestamp('graded_at')->nullable()->after('completed_at'));
        }

        if (! Schema::hasTable('quiz_attempt_attachments')) {
            Schema::create('quiz_attempt_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
                $table->foreignId('quiz_attempt_question_id')->constrained()->cascadeOnDelete();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('disk')->default('local');
                $table->string('path');
                $table->string('original_name');
                $table->string('mime_type', 150)->nullable();
                $table->unsignedBigInteger('size');
                $table->timestamps();

                $table->index(['quiz_attempt_id', 'quiz_attempt_question_id'], 'quiz_attachment_question_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_attachments');

        if (Schema::hasTable('quiz_attempt_answers') && Schema::hasColumn('quiz_attempt_answers', 'grading_status')) {
            Schema::table('quiz_attempt_answers', function (Blueprint $table) {
                $table->dropForeign(['graded_by']);
                $table->dropColumn(['grading_status', 'score', 'rubric_scores', 'teacher_feedback', 'graded_by', 'graded_at']);
            });
        }

        if (Schema::hasTable('quiz_attempt_questions') && Schema::hasColumn('quiz_attempt_questions', 'grading_mode')) {
            Schema::table('quiz_attempt_questions', function (Blueprint $table) {
                $table->dropColumn(['grading_mode', 'max_score']);
            });
        }

        if (Schema::hasColumn('quiz_attempts', 'auto_score')) {
            Schema::table('quiz_attempts', function (Blueprint $table) {
                $table->dropColumn(['auto_score', 'manual_score', 'graded_at']);
            });
        }
    }
};
