<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_resource_locks', function (Blueprint $table): void {
            $table->id();
            $table->string('resource_key', 191)->unique();
            $table->timestamps();
        });

        Schema::create('schedule_change_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope', 20);
            $table->unsignedSmallInteger('schedule_count');
            $table->json('before_values');
            $table->json('after_values');
            $table->string('status', 20)->default('applied');
            $table->timestamp('expires_at');
            $table->foreignId('undone_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('undone_at')->nullable();
            $table->timestamps();

            $table->index(['created_by', 'status', 'expires_at'], 'schedule_changes_actor_status_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_change_batches');
        Schema::dropIfExists('schedule_resource_locks');
    }
};
