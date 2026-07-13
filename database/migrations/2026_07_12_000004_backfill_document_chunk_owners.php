<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $seen = [];
        DB::connection('mysql')->table('ai_operations')
            ->where('feature', 'document_embedding')
            ->whereNotNull('user_id')
            ->orderByDesc('id')
            ->get(['user_id', 'subject_id', 'metadata'])
            ->each(function ($operation) use (&$seen) {
                $metadata = json_decode($operation->metadata ?? '{}', true) ?: [];
                $name = $metadata['document_name'] ?? null;
                $courseId = (int) ($operation->subject_id ?? 0);
                if (! $name) {
                    return;
                }

                $key = $courseId.'|'.$name;
                if (isset($seen[$key])) {
                    return;
                }
                $seen[$key] = true;

                DB::connection('pgsql')->table('document_chunks')
                    ->where('course_id', $courseId)
                    ->where('document_name', $name)
                    ->whereNull('uploaded_by')
                    ->update(['uploaded_by' => $operation->user_id]);
            });
    }

    public function down(): void
    {
        // Ownership inferred from historical operations should not be discarded.
    }
};
