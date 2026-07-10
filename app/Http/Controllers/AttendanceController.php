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

        $columnCount = AttendanceColumn::where('course_id', $courseId)->count();

        if (!$isStudentView && $columnCount == 0) {
            // Tạo mặc định 10 buổi học (Dùng ngày hiện tại làm mẫu)
            for ($i = 0; $i < 10; $i++) {
                AttendanceColumn::create([
                    'course_id' => $courseId,
                    'name' => now()->addDays($i)->format('d/m'), // Tên cột là ngày tháng
                    'type' => 'attendance',
                    'order' => $i,
                ]);
            }
            // Tạo cột điểm
            foreach (['HS1', 'HS1 ', 'HS2', 'HS2 '] as $key => $name) {
                AttendanceColumn::create([
                    'course_id' => $courseId,
                    'name' => $name,
                    'type' => 'grade',
                    'order' => 50 + $key, // Cho order cao để nằm sau
                ]);
            }
            // Tạo cột ghi chú cuối cùng
            AttendanceColumn::create([
                'course_id' => $courseId,
                'name' => 'Ghi chú',
                'type' => 'note',
                'order' => 100,
            ]);
        }

        // Lấy cột sắp xếp theo Order
        $columns = AttendanceColumn::where('course_id', $courseId)->orderBy('order')->get();

        $rawData = AttendanceData::whereIn('attendance_column_id', $columns->pluck('id'))
            ->when($isStudentView, fn ($query) => $query->where('user_id', $user->id))
            ->get();
        $attendanceData = [];
        foreach ($rawData as $d) {
            $attendanceData[$d->user_id][$d->attendance_column_id] = $d->value;
        }

        return view('attendance.show', compact('course', 'students', 'columns', 'attendanceData', 'isStudentView'));
    }
    public function addColumn(Request $request, $courseId)
    {
        $type = $request->type;
        $lastOrder = 0;

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

        AttendanceColumn::create([
            'course_id' => $courseId,
            'name' => $request->name, // Giáo viên có thể nhập "20/04" hoặc "B11"
            'type' => $type,
            'order' => $newOrder,
        ]);

        return back()->with('success', 'Đã thêm cột mới vào vị trí phù hợp!');
    }

    public function save(Request $request, $courseId)
    {
        foreach ($request->input('data', []) as $columnId => $users) {
            foreach ($users as $userId => $value) {
                AttendanceData::updateOrCreate(['attendance_column_id' => $columnId, 'user_id' => $userId], ['value' => $value]);
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
