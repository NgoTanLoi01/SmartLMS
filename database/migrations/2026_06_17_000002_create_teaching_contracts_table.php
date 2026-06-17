<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('contract_number')->unique();
            $table->date('signed_date')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('received_amount', 15, 2)->default(0);
            $table->string('status')->default('unpaid');
            $table->date('received_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
            $table->index(['signed_date', 'received_date']);
        });

        Schema::create('teaching_contract_record', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_contract_id')->constrained('teaching_contracts')->cascadeOnDelete();
            $table->foreignId('teaching_record_id')->constrained('teaching_records')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['teaching_contract_id', 'teaching_record_id'], 'contract_record_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_contract_record');
        Schema::dropIfExists('teaching_contracts');
    }
};
