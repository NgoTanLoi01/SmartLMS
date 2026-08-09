<?php

namespace App\Http\Controllers;

use App\Application\Gradebook\FinalizeGrades;
use App\Application\Gradebook\RecordGrade;
use App\Domain\Gradebook\GradebookException;
use App\Http\Requests\Gradebook\FinalizeGradeRequest;
use App\Http\Requests\Gradebook\RecordGradeRequest;
use App\Http\Requests\Gradebook\ReopenGradeRequest;
use App\Models\Course;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use App\Models\User;
use App\Queries\Gradebook\TeacherGradebookGridQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GradebookController extends Controller
{
    public function index(Request $request, Course $course, TeacherGradebookGridQuery $grid)
    {
        Gate::authorize('update', $course);
        abort_unless(Schema::hasTable('grading_periods'), 503, 'Gradebook chưa được khởi tạo. Vui lòng chạy migration additive trước.');
        $periods = $course->gradingPeriods()->orderByDesc('starts_at')->orderBy('name')->get();
        $period = $request->filled('period_id')
            ? $periods->firstWhere('id', $request->integer('period_id'))
            : $periods->first();
        abort_if($request->filled('period_id') && ! $period, 404);

        $gridData = $period ? $grid->get($period) : ['students' => null, 'rows' => collect()];

        return view('gradebook.index', compact('course', 'periods', 'period') + $gridData);
    }

    public function record(
        RecordGradeRequest $request,
        GradingPeriod $period,
        GradeItem $item,
        User $student,
        RecordGrade $recordGrade,
    ) {
        $this->assertScope($period, $item, $student);
        $status = (string) $request->validated('status');
        try {
            $recordGrade->handle(
                $item,
                $student,
                $status,
                $status === Grade::STATUS_GRADED ? (string) $request->validated('raw_points') : null,
                $request->user(),
                $request->validated('reason'),
                expectedVersion: $request->validated('expected_version'),
                correlationId: (string) Str::uuid(),
                source: 'teacher_ui',
            );
        } catch (GradebookException $exception) {
            return back()->withErrors(['gradebook' => $exception->getMessage()]);
        }

        return back()->with('success', 'Đã lưu điểm và ghi lịch sử thay đổi.');
    }

    public function finalize(
        FinalizeGradeRequest $request,
        GradingPeriod $period,
        User $student,
        FinalizeGrades $finalizeGrades,
    ) {
        $this->assertStudentScope($period, $student);
        try {
            $finalizeGrades->finalize($period, $student, $request->user(), (string) Str::uuid());
        } catch (GradebookException $exception) {
            return back()->withErrors(['gradebook' => $exception->getMessage()]);
        }

        return back()->with('success', 'Đã chốt điểm học viên.');
    }

    public function reopen(
        ReopenGradeRequest $request,
        GradingPeriod $period,
        User $student,
        FinalizeGrades $finalizeGrades,
    ) {
        $this->assertStudentScope($period, $student);
        try {
            $finalizeGrades->reopen($period, $student, $request->user(), $request->validated('reason'));
        } catch (GradebookException $exception) {
            return back()->withErrors(['gradebook' => $exception->getMessage()]);
        }

        return back()->with('success', 'Đã mở lại điểm và ghi nhận lý do.');
    }

    private function assertScope(GradingPeriod $period, GradeItem $item, User $student): void
    {
        abort_unless((int) $item->grading_period_id === (int) $period->id, 404);
        $this->assertStudentScope($period, $student);
    }

    private function assertStudentScope(GradingPeriod $period, User $student): void
    {
        abort_unless($student->isStudent() && $student->classes()->whereHas('courses', fn ($query) => $query->where('courses.id', $period->course_id))->exists(), 404);
    }
}
