<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->table('document_chunks', function (Blueprint $table) {
            // Users live in MySQL, therefore this intentionally is not a cross-database foreign key.
            $table->unsignedBigInteger('uploaded_by')->nullable()->after('course_id')->index();
            $table->index(['course_id', 'document_name', 'uploaded_by'], 'document_chunks_owner_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->table('document_chunks', function (Blueprint $table) {
            $table->dropIndex('document_chunks_owner_lookup_idx');
            $table->dropColumn('uploaded_by');
        });
    }
};
