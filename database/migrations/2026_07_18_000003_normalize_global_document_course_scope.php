<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        DB::connection('pgsql')
            ->table('document_chunks')
            ->where('course_id', 0)
            ->update(['course_id' => null]);
    }

    public function down(): void
    {
        DB::connection('pgsql')
            ->table('document_chunks')
            ->whereNull('course_id')
            ->update(['course_id' => 0]);
    }
};
