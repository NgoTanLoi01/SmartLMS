<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('missing_policy', 20)->default('block');
            $table->unsignedTinyInteger('rounding_precision')->default(1);
            $table->string('rounding_mode', 20)->default('half_up');
            $table->unsignedInteger('calculation_version')->default(1);
            $table->timestamps();

            $table->unique(['course_id', 'code']);
            $table->index(['course_id', 'status']);
        });

        Schema::create('grade_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grading_period_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name');
            $table->decimal('weight_percent', 7, 4);
            $table->string('aggregation_method', 30)->default('weighted_mean');
            $table->boolean('allow_over_max')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['grading_period_id', 'code']);
            $table->index(['course_id', 'grading_period_id', 'position'], 'grade_categories_scope_position_idx');
        });

        Schema::create('grade_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grading_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_category_id')->constrained()->cascadeOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->string('item_type', 30);
            $table->string('source_type', 40)->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('max_points', 12, 4);
            $table->decimal('item_weight', 12, 4)->default(1);
            $table->string('attempt_policy', 30)->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['grading_period_id', 'code']);
            $table->unique(
                ['grading_period_id', 'source_type', 'source_id'],
                'grade_items_period_source_unique'
            );
            $table->index(
                ['course_id', 'grading_period_id', 'grade_category_id', 'position'],
                'grade_items_scope_position_idx'
            );
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grade_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('ungraded');
            $table->decimal('raw_points', 12, 4)->nullable();
            $table->decimal('effective_points', 12, 4)->nullable();
            $table->string('source_version', 191)->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['grade_item_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['grade_item_id', 'status']);
        });

        Schema::create('grade_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grading_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('grade_category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('scope', 20);
            $table->decimal('amount', 12, 4);
            $table->text('reason');
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('adjusted_at');
            $table->foreignId('reverses_adjustment_id')->nullable()->constrained('grade_adjustments')->nullOnDelete();
            $table->string('idempotency_key', 191)->unique();
            $table->timestamps();

            $table->index(['user_id', 'grading_period_id', 'adjusted_at'], 'grade_adjustments_student_period_idx');
        });

        Schema::create('grade_change_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grade_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('grade_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('grading_period_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('reason')->nullable();
            $table->string('source', 50)->default('application');
            $table->string('correlation_id', 191);
            $table->string('request_id', 191)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['correlation_id', 'action', 'grade_id'],
                'grade_change_logs_correlation_action_grade_unique'
            );
            $table->index(['grade_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['grading_period_id', 'created_at'], 'grade_change_logs_period_created_idx');
        });

        Schema::create('grade_finalizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grading_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('state', 20)->default('draft');
            $table->decimal('final_score', 12, 4)->nullable();
            $table->decimal('unrounded_score', 16, 8)->nullable();
            $table->json('formula_snapshot')->nullable();
            $table->json('grade_snapshot')->nullable();
            $table->char('calculation_hash', 64)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->unique(['grading_period_id', 'user_id']);
            $table->index(['course_id', 'grading_period_id', 'state'], 'grade_finalizations_scope_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_finalizations');
        Schema::dropIfExists('grade_change_logs');
        Schema::dropIfExists('grade_adjustments');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('grade_items');
        Schema::dropIfExists('grade_categories');
        Schema::dropIfExists('grading_periods');
    }
};
