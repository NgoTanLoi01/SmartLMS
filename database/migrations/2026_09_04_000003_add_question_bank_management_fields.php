<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->json('tags')->nullable()->after('difficulty');
            $table->unsignedInteger('current_version')->default(1)->after('tags');
        });

        Schema::create('question_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('snapshot');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_type', 40);
            $table->string('change_summary', 500)->nullable();
            $table->timestamps();

            $table->unique(['question_id', 'version_number']);
            $table->index(['question_id', 'created_at']);
        });

        DB::table('questions')->orderBy('id')->chunkById(200, function ($questions): void {
            $questionIds = $questions->pluck('id');
            $options = DB::table('options')
                ->whereIn('question_id', $questionIds)
                ->orderBy('id')
                ->get()
                ->groupBy('question_id');
            $now = now();
            $rows = $questions->map(function ($question) use ($options, $now): array {
                $snapshot = [
                    'course_id' => $question->course_id,
                    'question_bank_id' => $question->question_bank_id,
                    'quiz_passage_id' => $question->quiz_passage_id,
                    'question_type' => $question->question_type,
                    'question_text' => $question->question_text,
                    'answer_config' => $question->answer_config ? json_decode($question->answer_config, true) : null,
                    'difficulty' => $question->difficulty,
                    'tags' => [],
                    'status' => $question->status,
                    'options' => collect($options->get($question->id, []))->map(fn ($option) => [
                        'option_text' => $option->option_text,
                        'is_correct' => (bool) $option->is_correct,
                    ])->values()->all(),
                ];

                return [
                    'question_id' => $question->id,
                    'version_number' => 1,
                    'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'changed_by' => null,
                    'change_type' => 'baseline',
                    'change_summary' => 'Khởi tạo lịch sử từ dữ liệu hiện có.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            if ($rows !== []) {
                DB::table('question_versions')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_versions');
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn(['tags', 'current_version']);
        });
    }
};
