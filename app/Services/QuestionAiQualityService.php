<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Question;
use Illuminate\Support\Str;

class QuestionAiQualityService
{
    public function reviewBatch(Course|int $course, array $questions): array
    {
        $course = $course instanceof Course ? $course : Course::findOrFail($course);
        $bankIds = $course->questionBanks()->pluck('question_banks.id');
        $existing = Question::query()
            ->select(['id', 'question_text'])
            ->notArchived()
            ->where(function ($query) use ($course, $bankIds) {
                if ($bankIds->isNotEmpty()) {
                    $query->whereIn('question_bank_id', $bankIds);
                }
                $query->orWhere('course_id', $course->id);
            })
            ->latest('id')
            ->limit(1500)
            ->get()
            ->map(fn ($question) => [
                'id' => (int) $question->id,
                'text' => $question->question_text,
                'normalized' => $this->normalize($question->question_text),
            ]);

        $seen = [];

        return collect($questions)->values()->map(function (array $question, int $index) use ($existing, &$seen) {
            $warnings = collect($question['quality_review'] ?? [])->map(fn ($warning) => trim((string) $warning))->filter()->values();
            $normalized = $this->normalize((string) ($question['question'] ?? ''));

            if (mb_strlen(trim((string) ($question['question'] ?? ''))) < 15) {
                $warnings->push('Nội dung câu hỏi quá ngắn, giáo viên nên bổ sung ngữ cảnh.');
            }
            if (trim((string) ($question['explanation'] ?? '')) === '') {
                $warnings->push('AI chưa cung cấp giải thích cho đáp án.');
            }

            $best = null;
            foreach ($existing as $candidate) {
                $similarity = $this->similarity($normalized, $candidate['normalized']);
                if ($similarity >= 82 && (! $best || $similarity > $best['similarity'])) {
                    $best = ['question_id' => $candidate['id'], 'text' => $candidate['text'], 'similarity' => $similarity];
                }
            }
            foreach ($seen as $candidateIndex => $candidateText) {
                $similarity = $this->similarity($normalized, $candidateText);
                if ($similarity >= 82 && (! $best || $similarity > $best['similarity'])) {
                    $best = ['batch_index' => $candidateIndex, 'similarity' => $similarity];
                }
            }
            $seen[$index] = $normalized;

            if ($best) {
                $target = isset($best['question_id']) ? 'câu #'.$best['question_id'].' trong ngân hàng' : 'một câu khác trong đợt sinh';
                $warnings->push("Nội dung giống {$target} khoảng {$best['similarity']}%.");
            }

            $warnings = $warnings->unique()->values();
            $question['quality'] = [
                'status' => $warnings->isEmpty() ? 'good' : 'needs_review',
                'score' => max(0, 100 - ($warnings->count() * 12) - ($best ? 15 : 0)),
                'warnings' => $warnings->all(),
                'duplicate' => $best,
            ];

            return $question;
        })->all();
    }

    private function normalize(string $text): string
    {
        $text = Str::lower(strip_tags($text));
        $text = preg_replace('/[^\pL\pN]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function similarity(string $left, string $right): int
    {
        if ($left === '' || $right === '') {
            return 0;
        }
        if ($left === $right) {
            return 100;
        }
        similar_text($left, $right, $percent);

        return (int) round($percent);
    }
}
