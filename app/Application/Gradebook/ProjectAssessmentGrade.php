<?php

namespace App\Application\Gradebook;

use App\Domain\Gradebook\GradebookException;
use App\Models\AssignmentSubmission;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProjectAssessmentGrade
{
    public function __construct(private RecordGrade $recordGrade) {}

    public function assignment(AssignmentSubmission $submission, User $actor): int
    {
        if (! $this->enabled() || $submission->grade === null) {
            return 0;
        }

        $submission->loadMissing(['assignment', 'user']);
        if (! $submission->assignment) {
            return 0;
        }

        $items = $this->linkedItems(
            GradeItem::SOURCE_ASSIGNMENT,
            $submission->assignment_id,
            $submission->assignment->course_id,
        );

        return $this->project(
            $items,
            $submission->user,
            (string) $submission->grade,
            $actor,
            'assignment_submission:'.$submission->id.':'.hash('sha256', (string) $submission->grade.'|'.$submission->updated_at),
            'assignment_projection',
        );
    }

    public function quiz(QuizAttempt $attempt, User $actor): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        $attempt->loadMissing('quiz');
        if (! $attempt->quiz) {
            return 0;
        }

        $items = $this->linkedItems(GradeItem::SOURCE_QUIZ, $attempt->quiz_id, $attempt->quiz->course_id);
        $written = 0;

        foreach ($items as $item) {
            $selected = $this->selectedReleasedAttempt($attempt, $item->attempt_policy ?? 'highest_released');
            if (! $selected) {
                continue;
            }

            $written += $this->project(
                collect([$item]),
                $selected->user,
                (string) $selected->score,
                $actor,
                'quiz_attempt:'.$selected->id.':'.hash('sha256', (string) $selected->score.'|'.$selected->updated_at),
                'quiz_projection',
            );
        }

        return $written;
    }

    private function enabled(): bool
    {
        return (bool) config('gradebook.projection_enabled', true)
            && Schema::hasTable('grade_items')
            && Schema::hasTable('grades')
            && Schema::hasTable('grade_change_logs');
    }

    /** @return Collection<int,GradeItem> */
    private function linkedItems(string $sourceType, int $sourceId, int $courseId): Collection
    {
        return GradeItem::query()
            ->with('category')
            ->where('course_id', $courseId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->get();
    }

    private function selectedReleasedAttempt(QuizAttempt $attempt, string $policy): ?QuizAttempt
    {
        if (! in_array($policy, ['highest_released', 'latest_released', 'first_released'], true)) {
            Log::warning('Gradebook quiz projection skipped: unsupported attempt policy', [
                'quiz_id' => $attempt->quiz_id,
                'attempt_policy' => $policy,
            ]);

            return null;
        }

        $attempts = QuizAttempt::query()
            ->where('quiz_id', $attempt->quiz_id)
            ->where('user_id', $attempt->user_id)
            ->resultsReleased()
            ->whereNotNull('score')
            ->get();

        return match ($policy) {
            'latest_released' => $attempts->sortByDesc('completed_at')->first(),
            'first_released' => $attempts->sortBy('completed_at')->first(),
            'highest_released' => $attempts->sort(function (QuizAttempt $left, QuizAttempt $right): int {
                $scoreComparison = bccomp((string) $right->score, (string) $left->score, 4);

                return $scoreComparison !== 0
                    ? $scoreComparison
                    : ($right->completed_at?->getTimestamp() ?? 0) <=> ($left->completed_at?->getTimestamp() ?? 0);
            })->first(),
        };
    }

    /** @param Collection<int,GradeItem> $items */
    private function project(
        Collection $items,
        ?User $student,
        string $points,
        User $actor,
        string $sourceVersion,
        string $source,
    ): int {
        if (! $student) {
            return 0;
        }

        $written = 0;
        foreach ($items as $item) {
            try {
                $this->recordGrade->handle(
                    $item,
                    $student,
                    Grade::STATUS_GRADED,
                    $points,
                    $actor,
                    'Đồng bộ shadow từ Assessment',
                    $sourceVersion,
                    correlationId: "{$source}:{$item->id}:{$student->id}:{$sourceVersion}",
                    source: $source,
                );
                $written++;
            } catch (GradebookException $exception) {
                if (config('gradebook.read_source') === 'gradebook') {
                    throw $exception;
                }

                // Gradebook vẫn là shadow read-model trong giai đoạn reconciliation. Cấu hình
                // lệch hoặc grade đã finalize phải được báo động nhưng không làm hỏng flow legacy.
                Log::warning('Gradebook shadow projection skipped', [
                    'grade_item_id' => $item->id,
                    'student_id' => $student->id,
                    'source' => $source,
                    'source_version' => $sourceVersion,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $written;
    }
}
