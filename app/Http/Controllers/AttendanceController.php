<?php

namespace App\Http\Controllers;

use App\Application\Attendance\SaveAttendance;
use App\Exports\AttendanceExport;
use App\Http\Requests\Attendance\SaveAttendanceRequest;
use App\Models\AttendanceColumn;
use App\Models\AttendanceData;
use App\Models\Course;
use App\Support\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function exportExcel($courseId)
    {
        $course = Course::findOrFail($courseId);
        Gate::authorize('manageAttendance', $course);
        $students = $course->classes->flatMap->students->unique('id');
        $columns = AttendanceColumn::where('course_id', $courseId)->orderBy('order')->get();
        $rawData = AttendanceData::whereIn('attendance_column_id', $columns->pluck('id'))->get();

        $attendanceData = [];
        foreach ($rawData as $d) {
            $attendanceData[$d->user_id][$d->attendance_column_id] = $d->value;
        }

        $courseFileName = Str::ascii($course->title);
        $courseFileName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $courseFileName);
        $courseFileName = preg_replace('/_+/', '_', $courseFileName);
        $courseFileName = trim($courseFileName, '_-');
        $courseFileName = Str::limit($courseFileName, 120, '');
        $courseFileName = $courseFileName !== '' ? $courseFileName : 'Khoa_hoc_'.$course->id;

        return Excel::download(
            new AttendanceExport($course, $students, $columns, $attendanceData),
            $courseFileName.'.xlsx'
        );
    }

    public function show($courseId)
    {
        $course = Course::with('classes.students')->findOrFail($courseId);
        Gate::authorize('view', $course);
        $user = auth()->user();
        $isStudentView = $user->isStudent();

        if ($isStudentView) {
            $students = collect([$user]);
        } else {
            $students = $course->classes->flatMap->students->unique('id');
        }

        // Lấy cột sắp xếp theo Order
        $columns = AttendanceColumn::with('schedule')->where('course_id', $courseId)->orderBy('order')->get();
        $columnTypes = $columns->pluck('type', 'id');

        $rawData = AttendanceData::whereIn('attendance_column_id', $columns->pluck('id'))
            ->when($isStudentView, fn ($query) => $query->where('user_id', $user->id))
            ->get();
        $attendanceData = [];
        $attendanceNotes = [];
        foreach ($rawData as $d) {
            $attendanceData[$d->user_id][$d->attendance_column_id] = $columnTypes->get($d->attendance_column_id) === 'attendance'
                ? AttendanceStatus::normalize($d->value)
                : $d->value;
            $attendanceNotes[$d->user_id][$d->attendance_column_id] = $d->note;
        }

        $schedules = DB::table('schedules')
            ->join('classes', 'schedules.class_id', '=', 'classes.id')
            ->where('schedules.course_id', $courseId)
            ->where('schedules.status', 'active')
            ->where('classes.status', 'active')
            ->orderByDesc('schedules.schedule_date')
            ->orderBy('schedules.start_time')
            ->select('schedules.*', 'classes.name as class_name')
            ->get();

        return view('attendance.show', compact('course', 'students', 'columns', 'attendanceData', 'attendanceNotes', 'schedules', 'isStudentView'));
    }

    public function addColumn(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);
        Gate::authorize('create', [AttendanceColumn::class, $course]);

        $validated = $request->validate([
            'type' => 'required|in:attendance,grade,note',
            'name' => 'nullable|string|max:100',
            'schedule_id' => 'nullable|integer|exists:schedules,id',
            'attendance_date' => 'nullable|date',
        ]);
        $type = $validated['type'];
        $lastOrder = 0;
        $schedule = null;

        if ($type === 'attendance' && ! empty($validated['schedule_id'])) {
            $schedule = DB::table('schedules')
                ->where('id', $validated['schedule_id'])
                ->where('course_id', $courseId)
                ->first();
            abort_unless($schedule, 422, 'Lịch học không thuộc khóa học này.');

            if (AttendanceColumn::where('course_id', $courseId)->where('schedule_id', $schedule->id)->exists()) {
                return back()->with('error', 'Lịch học này đã có buổi điểm danh.');
            }
        }

        if ($type == 'attendance') {
            // Nếu thêm điểm danh, lấy order của cột điểm danh cuối cùng
            $lastOrder = AttendanceColumn::where('course_id', $courseId)->where('type', 'attendance')->max('order') ?? 0;
            $newOrder = $lastOrder + 1;
        } elseif ($type == 'grade') {
            $lastOrder = AttendanceColumn::where('course_id', $courseId)->where('type', 'grade')->max('order') ?? 50;
            $newOrder = $lastOrder + 1;
        } else {
            $newOrder = 100; // Ghi chú luôn là 100
        }

        $attendanceDate = $type === 'attendance'
            ? ($schedule?->schedule_date ?? ($validated['attendance_date'] ?? now()->toDateString()))
            : null;
        $name = trim((string) ($validated['name'] ?? ''));
        if ($type === 'attendance' && $name === '') {
            $name = $attendanceDate ? Carbon::parse($attendanceDate)->format('d/m/Y') : now()->format('d/m/Y');
        }
        if ($type !== 'attendance' && $name === '') {
            return back()->withErrors(['name' => 'Vui lòng nhập tên cột.'])->withInput();
        }

        AttendanceColumn::create([
            'course_id' => $courseId,
            'schedule_id' => $schedule?->id,
            'attendance_date' => $attendanceDate,
            'name' => $name,
            'type' => $type,
            'order' => $newOrder,
        ]);

        return back()->with('success', 'Đã thêm cột mới vào vị trí phù hợp!');
    }

    public function save(SaveAttendanceRequest $request, $courseId, SaveAttendance $saveAttendance)
    {
        $course = Course::findOrFail($courseId);
        Gate::authorize('manageAttendance', $course);

        if (! $saveAttendance->handle($course, $request->validated())) {
            return back()->with('success', 'Không có thay đổi cần lưu.');
        }

        return back()->with('success', 'Đã lưu bảng điểm danh thành công!');
    }

    // Xóa cột
    public function deleteColumn($columnId)
    {
        $column = AttendanceColumn::findOrFail($columnId);
        Gate::authorize('delete', $column);
        // Khi xóa cột, các dữ liệu trong bảng attendance_data sẽ tự động bị xóa do ràng buộc cascade
        $column->delete();

        return back()->with('success', 'Đã xóa cột thành công!');
    }

    // Cập nhật tên cột (để bạn đổi B1, B2 thành ngày tháng)
    public function updateColumn(Request $request, $columnId)
    {
        $column = AttendanceColumn::findOrFail($columnId);
        Gate::authorize('update', $column);
        $validated = $request->validate(['name' => 'required|string|max:100']);
        $column->update([
            'name' => $validated['name'],
        ]);

        return response()->json(['success' => true]);
    }
}
