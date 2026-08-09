<?php

namespace App\Queries\Gradebook;

use App\Domain\Gradebook\GradebookException;
use App\Domain\Gradebook\GradeCalculationService;
use App\Models\GradeAdjustment;
use App\Models\GradeFinalization;
use App\Models\GradingPeriod;
use App\Models\User;

class TeacherGradebookGridQuery
{
    public function __construct(private GradeCalculationService $calculator) {}

    /** @return array<string,mixed> */
    public function get(GradingPeriod $period, int $perPage = 25): array
    {
        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereHas('classes.courses', fn ($query) => $query->where('courses.id', $period->course_id))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
        $studentIds = $students->getCollection()->pluck('id');

        $period->load([
            'categories' => fn ($query) => $query->where('is_active', true)->orderBy('position'),
            'categories.items' => fn ($query) => $query->where('is_published', true)->orderBy('position'),
            'categories.items.grades' => fn ($query) => $query->whereIn('user_id', $studentIds),
        ]);
        $adjustments = GradeAdjustment::query()
            ->where('grading_period_id', $period->id)
            ->whereIn('user_id', $studentIds)
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');
        $finalizations = GradeFinalization::query()
            ->where('grading_period_id', $period->id)
            ->whereIn('user_id', $studentIds)
            ->get()
            ->keyBy('user_id');

        $rows = $students->getCollection()->map(function (User $student) use ($period, $adjustments, $finalizations): array {
            try {
                $calculation = $this->calculator->calculateLoaded(
                    $period,
                    $student->id,
                    $adjustments->get($student->id, collect()),
                );
                $calculationError = null;
            } catch (GradebookException $exception) {
                $calculation = null;
                $calculationError = $exception->getMessage();
            }

            return [
                'student' => $student,
                'calculation' => $calculation,
                'calculation_error' => $calculationError,
                'finalization' => $finalizations->get($student->id),
            ];
        });

        return compact('students', 'rows');
    }
}
