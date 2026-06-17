<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_contracts', function (Blueprint $table) {
            $table->text('evidence_url')->nullable()->after('received_date');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_contracts', function (Blueprint $table) {
            $table->dropColumn('evidence_url');
        });
    }
};
