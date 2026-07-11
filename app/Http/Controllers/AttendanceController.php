<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\AttendanceColumn;
use App\Models\AttendanceData;
use Illuminate\Http\Request;
use App\Exports\AttendanceExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\NotificationCenter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function exportExcel($courseId)
    {
        $course = Course::findOrFail($courseId);
        $students = $course->classes->flatMap->students->unique('id');
        $columns = AttendanceColumn::where('course_id', $courseId)->orderBy('order')->get();
        $rawData = AttendanceData::whereIn('attendance_column_id', $columns->pluck('id'))->get();

        $attendanceData = [];
        foreach ($rawData as $d) {
            $attendanceData[$d->user_id][$d->attendance_column_id] = $d->value;
        }

        return Excel::download(new AttendanceExport($course, $students, $columns, $attendanceData), 'Diem_Danh_' . $course->id . '.xlsx');
    }
    public function show($courseId)
    {
        $course = Course::with('classes.students')->findOrFail($courseId);
        $user = auth()->user();
        $isStudentView = $user->isStudent();

        if ($isStudentView) {
            $isEnrolled = $course->isVisibleToStudents()
                && $course->classes
                    ->where('status', 'active')
                    ->flatMap->students
                    ->contains('id', $user->id);

            abort_unless($isEnrolled, 403, 'Bạn không tham gia khóa học này.');
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
                ? $this->normalizeAttendanceStatus($d->value)
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
        $validated = $request->validate([
            'type' => 'required|in:attendance,grade,note',
            'name' => 'nullable|string|max:100',
            'schedule_id' => 'nullable|integer|exists:schedules,id',
            'attendance_date' => 'nullable|date',
        ]);
        $type = $validated['type'];
        $lastOrder = 0;
        $schedule = null;

        if ($type === 'attendance' && !empty($validated['schedule_id'])) {
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
            $name = $attendanceDate ? \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y') : now()->format('d/m/Y');
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

    public function save(Request $request, $courseId)
    {
        $columns = AttendanceColumn::where('course_id', $courseId)->get()->keyBy('id');
        $allowedUserIds = Course::with('classes.students')->findOrFail($courseId)
            ->classes->flatMap->students->pluck('id')->unique();

        foreach ($request->input('data', []) as $columnId => $users) {
            $column = $columns->get((int) $columnId);
            if (!$column) continue;

            foreach ($users as $userId => $value) {
                if (!$allowedUserIds->contains((int) $userId)) continue;

                $savedValue = $column->type === 'attendance' ? $this->normalizeAttendanceStatus($value) : $value;
                AttendanceData::updateOrCreate(
                    ['attendance_column_id' => $column->id, 'user_id' => $userId],
                    [
                        'value' => $savedValue,
                        'note' => $request->input("notes.{$column->id}.{$userId}"),
                    ]
                );
            }
        }

        $this->notifyFrequentAbsences((int) $courseId, collect($request->input('data', []))->flatMap(fn ($users) => array_keys($users))->unique());

        return back()->with('success', 'Đã lưu bảng điểm danh thành công!');
    }

    private function notifyFrequentAbsences(int $courseId, $userIds): void
    {
        $attendanceColumnIds = AttendanceColumn::where('course_id', $courseId)
            ->where('type', 'attendance')
            ->pluck('id');

        foreach ($userIds as $userId) {
            $absenceCount = AttendanceData::where('user_id', $userId)
                ->whereIn('attendance_column_id', $attendanceColumnIds)
                ->pluck('value')
                ->filter(fn ($value) => $this->isAbsentValue($value))
                ->count();

            if ($absenceCount >= 3) {
                app(NotificationCenter::class)->notifyUser(
                    (int) $userId,
                    'attendance_warning',
                    'Cảnh báo chuyên cần',
                    "Bạn đã có {$absenceCount} lượt vắng/nghỉ trong khóa học. Hãy trao đổi với giáo viên nếu cần hỗ trợ.",
                    route('attendance.show', $courseId),
                    ['course_id' => $courseId, 'absence_count' => $absenceCount],
                    "attendance-warning:{$courseId}:{$userId}:{$absenceCount}"
                );
            }
        }
    }

    private function isAbsentValue($value): bool
    {
        $normalized = Str::of((string) $value)->lower()->ascii()->trim()->toString();

        return in_array($normalized, ['0', 'no', 'false', 'abs', 'absent', 'v', 'vang', 'nghi'], true)
            || str_contains(Str::lower((string) $value), 'vắng')
            || str_contains(Str::lower((string) $value), 'nghỉ');
    }

    private function normalizeAttendanceStatus($value): string
    {
        $normalized = Str::of((string) $value)->lower()->ascii()->trim()->toString();

        if (in_array($normalized, ['absent', 'v', 'vang', 'nghi', '0', 'no', 'false'], true)) return 'absent';
        if (in_array($normalized, ['late', 'muon', 'di muon'], true)) return 'late';
        if (in_array($normalized, ['excused', 'phep', 'co phep', 'vang co phep'], true)) return 'excused';

        return 'present';
    }
    // Xóa cột
    public function deleteColumn($columnId)
    {
        $column = AttendanceColumn::findOrFail($columnId);
        // Khi xóa cột, các dữ liệu trong bảng attendance_data sẽ tự động bị xóa do ràng buộc cascade
        $column->delete();
        return back()->with('success', 'Đã xóa cột thành công!');
    }

    // Cập nhật tên cột (để bạn đổi B1, B2 thành ngày tháng)
    public function updateColumn(Request $request, $columnId)
    {
        $column = AttendanceColumn::findOrFail($columnId);
        $column->update([
            'name' => $request->name,
        ]);
        return response()->json(['success' => true]);
    }
}
