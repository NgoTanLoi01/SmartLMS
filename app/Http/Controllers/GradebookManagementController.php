<?php

namespace App\Http\Controllers;

use App\Application\Gradebook\AdjustGrade;
use App\Application\Gradebook\SetGradeItemLock;
use App\Application\Gradebook\SetGradingPeriodStatus;
use App\Domain\Gradebook\GradebookException;
use App\Http\Requests\Gradebook\AdjustGradeRequest;
use App\Http\Requests\Gradebook\FinalizeGradeRequest;
use App\Http\Requests\Gradebook\ReopenGradingPeriodRequest;
use App\Http\Requests\Gradebook\ReverseGradeAdjustmentRequest;
use App\Http\Requests\Gradebook\SetGradeItemLockRequest;
use App\Models\Grade;
use App\Models\GradeAdjustment;
use App\Models\GradeItem;
use App\Models\GradingPeriod;
use App\Queries\Gradebook\GradebookHistoryQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GradebookManagementController extends Controller
{
    public function lockItem(SetGradeItemLockRequest $request, GradingPeriod $period, GradeItem $item, SetGradeItemLock $lock)
    {
        $this->assertItemScope($period, $item);

        return $this->run(fn () => $lock->handle($item, true, $request->user(), $request->validated('expected_version')), 'Đã khóa thành phần điểm cho toàn lớp.');
    }

    public function unlockItem(SetGradeItemLockRequest $request, GradingPeriod $period, GradeItem $item, SetGradeItemLock $lock)
    {
        $this->assertItemScope($period, $item);

        return $this->run(fn () => $lock->handle($item, false, $request->user(), $request->validated('expected_version')), 'Đã mở khóa thành phần điểm.');
    }

    public function closePeriod(FinalizeGradeRequest $request, GradingPeriod $period, SetGradingPeriodStatus $status)
    {
        return $this->run(fn () => $status->close($period, $request->user()), 'Đã đóng toàn bộ kỳ điểm.');
    }

    public function reopenPeriod(ReopenGradingPeriodRequest $request, GradingPeriod $period, SetGradingPeriodStatus $status)
    {
        return $this->run(fn () => $status->reopen($period, $request->user(), $request->validated('reason')), 'Đã mở lại kỳ điểm. Các thành phần vẫn được khóa để tránh sửa nhầm.');
    }

    public function adjust(AdjustGradeRequest $request, GradingPeriod $period, Grade $grade, AdjustGrade $adjust)
    {
        $this->assertGradeScope($period, $grade);

        return $this->run(fn () => $adjust->handle(
            $grade,
            $request->validated('type'),
            (string) $request->validated('amount'),
            $request->validated('reason'),
            $request->user(),
            $request->validated('idempotency_key'),
        ), 'Đã ghi điều chỉnh điểm và lịch sử thay đổi.');
    }

    public function reverseAdjustment(
        ReverseGradeAdjustmentRequest $request,
        GradingPeriod $period,
        GradeAdjustment $adjustment,
        AdjustGrade $adjust,
    ) {
        abort_unless((int) $adjustment->grading_period_id === (int) $period->id, 404);

        return $this->run(fn () => $adjust->reverse(
            $adjustment,
            $request->validated('reason'),
            $request->user(),
            $request->validated('idempotency_key'),
        ), 'Đã hoàn tác điều chỉnh điểm.');
    }

    public function history(Request $request, GradingPeriod $period, GradebookHistoryQuery $history)
    {
        Gate::authorize('view', $period);
        $logs = $history->get($period, $request->integer('student_id') ?: null, $request->integer('item_id') ?: null);
        $period->load(['course', 'items']);

        return view('gradebook.history', compact('period', 'logs'));
    }

    private function assertItemScope(GradingPeriod $period, GradeItem $item): void
    {
        abort_unless((int) $item->grading_period_id === (int) $period->id, 404);
    }

    private function assertGradeScope(GradingPeriod $period, Grade $grade): void
    {
        abort_unless((int) $grade->item?->grading_period_id === (int) $period->id, 404);
    }

    private function run(callable $callback, string $message)
    {
        try {
            $callback();
        } catch (GradebookException $exception) {
            return back()->withErrors(['gradebook' => $exception->getMessage()]);
        }

        return back()->with('success', $message);
    }
}
