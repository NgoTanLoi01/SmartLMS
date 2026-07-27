<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\DB;

class QuestionDifficultyAnalyticsService
{
    public const MIN_SAMPLE_SIZE = 5;

    public function refreshForQuestionIds(iterable $questionIds): void
    {
        $ids = collect($questionIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        $metrics = DB::table('quiz_attempt_answers as answers')
            ->join('quiz_attempt_questions as issued', 'issued.id', '=', 'answers.quiz_attempt_question_id')
            ->join('quiz_attempts as attempts', 'attempts.id', '=', 'answers.quiz_attempt_id')
            ->whereIn('issued.question_id', $ids)
            ->where('attempts.status', 'submitted')
            ->whereNotNull('answers.is_correct')
            ->groupBy('issued.question_id')
            ->selectRaw('issued.question_id, COUNT(*) as sample_size, SUM(CASE WHEN answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_count')
            ->get()
            ->keyBy('question_id');

        foreach ($ids as $id) {
            $row = $metrics->get($id);
            $sampleSize = (int) ($row->sample_size ?? 0);
            $correctCount = (int) ($row->correct_count ?? 0);
            $accuracy = $sampleSize > 0 ? round($correctCount / $sampleSize, 4) : null;
            $observed = $sampleSize >= self::MIN_SAMPLE_SIZE ? $this->classify((float) $accuracy) : null;

            Question::find($id)?->update([
                'observed_difficulty' => $observed,
                'difficulty_metrics' => [
                    'sample_size' => $sampleSize,
                    'correct_count' => $correctCount,
                    'accuracy' => $accuracy,
                    'minimum_sample_size' => self::MIN_SAMPLE_SIZE,
                ],
                'difficulty_evaluated_at' => now(),
            ]);
        }
    }

    private function classify(float $accuracy): string
    {
        return match (true) {
            $accuracy >= 0.8 => 'easy',
            $accuracy <= 0.4 => 'hard',
            default => 'medium',
        };
    }
}
