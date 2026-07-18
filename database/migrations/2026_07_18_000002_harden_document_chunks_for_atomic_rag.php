<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        DB::connection('pgsql')->statement('ALTER EXTENSION vector UPDATE');

        Schema::connection('pgsql')->table('document_chunks', function (Blueprint $table) {
            $table->uuid('ingestion_id')->nullable();
            $table->unsignedInteger('chunk_index')->default(0);
            $table->unsignedInteger('page_number')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->index(['course_id', 'is_active'], 'document_chunks_course_active_idx');
            $table->index(['ingestion_id', 'is_active'], 'document_chunks_ingestion_active_idx');
        });

        DB::connection('pgsql')->statement(
            'CREATE INDEX IF NOT EXISTS document_chunks_embedding_hnsw_idx '
            .'ON document_chunks USING hnsw ((embedding::halfvec(3072)) halfvec_cosine_ops) '
            .'WITH (m = 16, ef_construction = 64) WHERE is_active = true'
        );
    }

    public function down(): void
    {
        DB::connection('pgsql')->statement('DROP INDEX IF EXISTS document_chunks_embedding_hnsw_idx');
        Schema::connection('pgsql')->table('document_chunks', function (Blueprint $table) {
            $table->dropIndex('document_chunks_course_active_idx');
            $table->dropIndex('document_chunks_ingestion_active_idx');
            $table->dropColumn(['ingestion_id', 'chunk_index', 'page_number', 'content_hash', 'is_active']);
        });
    }
};
