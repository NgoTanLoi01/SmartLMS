<?php

namespace App\Http\Controllers;

use App\Models\ScheduleAdjustmentBatch;
use App\Services\ScheduleBulkAdjustmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScheduleAdjustmentController extends Controller
{
    public function __construct(private ScheduleBulkAdjustmentService $adjustments) {}

    public function preview(Request $request)
    {
        $validated = $this->validated($request);

        return response()->json($this->adjustments->preview($request->user(), $validated));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $batch = $this->adjustments->apply($request->user(), $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã dời '.$batch->schedule_count.' buổi học và lưu lịch sử hoàn tác.',
            'batch_id' => $batch->public_id,
            'schedule_count' => $batch->schedule_count,
        ]);
    }

    public function undo(Request $request, ScheduleAdjustmentBatch $adjustment)
    {
        $batch = $this->adjustments->undo($request->user(), $adjustment);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã hoàn tác '.$batch->schedule_count.' buổi học về ngày cũ.',
            'batch_id' => $batch->public_id,
        ]);
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'class_ids' => 'required|array|min:1|max:100',
            'class_ids.*' => 'required|integer|distinct|exists:classes,id',
            'course_ids' => 'nullable|array|max:200',
            'course_ids.*' => 'required|integer|distinct|exists:courses,id',
            'date_from' => 'required|date_format:Y-m-d',
            'date_to' => 'required|date_format:Y-m-d|after_or_equal:date_from',
            'direction' => 'required|in:forward,backward',
            'shift_unit' => 'required|in:day,week',
            'shift_amount' => 'required|integer|min:1|max:365',
        ], [
            'class_ids.required' => 'Vui lòng chọn ít nhất một lớp học.',
            'date_to.after_or_equal' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.',
            'shift_amount.min' => 'Số ngày hoặc tuần cần dời phải lớn hơn 0.',
        ]);

        if (Carbon::parse($validated['date_from'])->diffInDays(Carbon::parse($validated['date_to'])) > 370) {
            throw ValidationException::withMessages([
                'date_to' => 'Khoảng lịch điều chỉnh không được vượt quá 370 ngày.',
            ]);
        }

        if ($validated['shift_unit'] === 'week' && (int) $validated['shift_amount'] > 52) {
            throw ValidationException::withMessages([
                'shift_amount' => 'Mỗi lần chỉ được dời tối đa 52 tuần.',
            ]);
        }

        $validated['class_ids'] = array_map('intval', $validated['class_ids']);
        $validated['course_ids'] = array_map('intval', $validated['course_ids'] ?? []);

        return $validated;
    }
}
