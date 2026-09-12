<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_adjustment_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->json('class_ids');
            $table->json('course_ids')->nullable();
            $table->string('shift_unit', 10);
            $table->unsignedSmallInteger('shift_amount');
            $table->smallInteger('shift_days');
            $table->unsignedInteger('schedule_count');
            $table->json('before_values');
            $table->json('after_values');
            $table->string('status', 20)->default('applied');
            $table->foreignId('undone_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('undone_at')->nullable();
            $table->timestamps();

            $table->index(['created_by', 'created_at'], 'schedule_adjustments_actor_created_idx');
            $table->index(['status', 'created_at'], 'schedule_adjustments_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_adjustment_batches');
    }
};
