<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->index();
            $table->string('title');
            $table->text('message');
            $table->string('action_url', 2048)->nullable();
            $table->json('data')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'dedupe_key']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_notifications');
    }
};
