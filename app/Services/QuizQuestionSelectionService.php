<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class QuizQuestionSelectionService
{
    public const DIFFICULTIES = [
        'easy' => 'Dễ',
        'medium' => 'Trung bình',
        'hard' => 'Khó',
    ];

    public function emptyDistribution(): array
    {
        return collect(Question::typeLabels())->mapWithKeys(fn ($label, $type) => [
            $type => array_fill_keys(array_keys(self::DIFFICULTIES), 0),
        ])->all();
    }

    public function normalizeDistribution(array $input): array
    {
        $distribution = $this->emptyDistribution();

        foreach ($distribution as $type => $difficulties) {
            foreach ($difficulties as $difficulty => $unused) {
                $value = data_get($input, "{$type}.{$difficulty}", 0);
                if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0 || (int) $value > 500) {
                    throw ValidationException::withMessages([
                        "question_distribution.{$type}.{$difficulty}" => 'Số lượng câu hỏi phải là số nguyên từ 0 đến 500.',
                    ]);
                }
                $distribution[$type][$difficulty] = (int) $value;
            }
        }

        if ($this->total($distribution) < 1) {
            throw ValidationException::withMessages([
                'question_distribution' => 'Đề thi phải có ít nhất một câu hỏi.',
            ]);
        }

        return $distribution;
    }

    public function availableCounts(Course $course): array
    {
        $counts = $this->emptyDistribution();
        $this->baseQuery($course)
            ->selectRaw('question_type, difficulty, COUNT(*) as aggregate')
            ->groupBy('question_type', 'difficulty')
            ->get()
            ->each(function ($row) use (&$counts) {
                if (isset($counts[$row->question_type][$row->difficulty])) {
                    $counts[$row->question_type][$row->difficulty] = (int) $row->aggregate;
                }
            });

        return $counts;
    }

    public function assertAvailable(Course $course, array $distribution): void
    {
        $available = $this->availableCounts($course);

        foreach ($distribution as $type => $difficulties) {
            foreach ($difficulties as $difficulty => $requested) {
                $stock = $available[$type][$difficulty] ?? 0;
                if ($requested > $stock) {
                    $typeLabel = Question::typeLabels()[$type] ?? $type;
                    $difficultyLabel = self::DIFFICULTIES[$difficulty] ?? $difficulty;
                    throw ValidationException::withMessages([
                        "question_distribution.{$type}.{$difficulty}" => "Không đủ câu {$typeLabel} - {$difficultyLabel}: cần {$requested}, hiện có {$stock}.",
                    ]);
                }
            }
        }
    }

    public function selectForQuiz(Quiz $quiz): Collection
    {
        if (! empty($quiz->question_distribution)) {
            $distribution = $this->normalizeDistribution($quiz->question_distribution);
            $questions = collect();

            foreach ($distribution as $type => $difficulties) {
                foreach ($difficulties as $difficulty => $limit) {
                    if ($limit < 1) {
                        continue;
                    }
                    $questions = $questions->merge(
                        $this->baseQuery($quiz->course)
                            ->with(['options', 'passage'])
                            ->where('question_type', $type)
                            ->where('difficulty', $difficulty)
                            ->inRandomOrder()
                            ->limit($limit)
                            ->get()
                    );
                }
            }

            return $questions->shuffle()->values();
        }

        $pick = fn (string $difficulty, int $limit) => $this->baseQuery($quiz->course)
            ->with(['options', 'passage'])
            ->where('difficulty', $difficulty)
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        return $pick('easy', (int) $quiz->easy_count)
            ->merge($pick('medium', (int) $quiz->medium_count))
            ->merge($pick('hard', (int) $quiz->hard_count))
            ->shuffle()
            ->values();
    }

    public function totalsByDifficulty(array $distribution): array
    {
        return collect(array_keys(self::DIFFICULTIES))->mapWithKeys(fn ($difficulty) => [
            $difficulty => collect($distribution)->sum(fn ($counts) => (int) ($counts[$difficulty] ?? 0)),
        ])->all();
    }

    public function total(array $distribution): int
    {
        return collect($distribution)->sum(fn ($counts) => array_sum($counts));
    }

    private function baseQuery(Course $course)
    {
        $bankIds = $course->questionBanks()->pluck('question_banks.id');

        return Question::query()
            ->notArchived()
            ->readyForExam()
            ->where(function ($query) use ($course, $bankIds) {
                if ($bankIds->isNotEmpty()) {
                    $query->whereIn('question_bank_id', $bankIds);
                }

                $query->orWhere('course_id', $course->id);
            });
    }
}
