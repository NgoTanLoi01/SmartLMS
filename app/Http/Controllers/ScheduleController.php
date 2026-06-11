<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Course;
use App\Models\Classroom;
use App\Imports\ScheduleImport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'student') {
            abort(403, 'Bạn chỉ có thể xem lịch học tại trang Tổng quan (Dashboard).');
        }

        if ($request->ajax() || $request->wantsJson() || $request->has('start')) {
            // SỬ DỤNG DB JOIN ĐỂ TRÁNH LỖI MODEL RELATIONSHIP
            $query = DB::table('schedules')->join('courses', 'schedules.course_id', '=', 'courses.id')->join('classes', 'schedules.class_id', '=', 'classes.id')->select('schedules.*', 'courses.title as course_title', 'classes.name as class_name');

            // Nếu là giáo viên, chỉ lấy lịch của họ
            if ($user->role === 'teacher') {
                $query->where('classes.teacher_id', $user->id);
            }

            $schedules = $query->get();
            $events = [];

            foreach ($schedules as $schedule) {
                // SỬ DỤNG CARBON ĐỂ CHUẨN HÓA CHUỖI NGÀY GIỜ ISO8601
                $date = \Carbon\Carbon::parse($schedule->schedule_date)->format('Y-m-d');
                $startTime = \Carbon\Carbon::parse($schedule->start_time)->format('H:i:s');
                $endTime = \Carbon\Carbon::parse($schedule->end_time)->format('H:i:s');
                $hasExamNote = trim((string) ($schedule->note ?? '')) !== '';

                $events[] = [
                    'id' => $schedule->id,
                    'title' => $schedule->course_title . ' (' . $schedule->class_name . ')' . ($hasExamNote ? ' - ' . $schedule->note : ''),
                    'start' => $date . 'T' . $startTime,
                    'end' => $date . 'T' . $endTime,
                    'extendedProps' => [
                        'class_id' => $schedule->class_id,
                        'course_id' => $schedule->course_id,
                        'room' => $schedule->room,
                        'note' => $schedule->note,
                    ],
                    'backgroundColor' => $hasExamNote ? '#dc2626' : '#0d6efd',
                    'borderColor' => $hasExamNote ? '#dc2626' : '#0d6efd',
                ];
            }
            return response()->json($events);
        }

        // CHỈ LOAD DANH SÁCH LỚP HỌC (Khóa học sẽ load sau bằng AJAX)
        if ($user->role === 'teacher') {
            $classes = DB::table('classes')->where('teacher_id', $user->id)->get();
        } else {
            $classes = DB::table('classes')->get();
        }

        return view('schedules.index', compact('classes'));
    }

    // HÀM MỚI: Lấy danh sách khóa học thuộc về 1 lớp cụ thể
    public function getCoursesByClass($class_id)
    {
        $courses = DB::table('class_course')->join('courses', 'class_course.course_id', '=', 'courses.id')->where('class_course.class_id', $class_id)->select('courses.id', 'courses.title')->get();

        return response()->json($courses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required',
            'course_id' => 'required',
            'schedule_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'note' => 'nullable|string|max:255',
        ]);

        $this->clearCourseExamNoteIfNeeded($request);

        Schedule::create($request->all());
        return response()->json(['status' => 'success', 'message' => 'Đã thêm lịch học!']);
    }

    public function copyDay(Request $request)
    {
        $validated = $request->validate([
            'source_date' => 'required|date',
            'target_date' => 'required|date|different:source_date',
        ], [
            'target_date.different' => 'Ngày muốn dán lịch phải khác ngày nguồn.',
        ]);

        $user = auth()->user();
        $sourceQuery = DB::table('schedules')
            ->join('classes', 'schedules.class_id', '=', 'classes.id')
            ->whereDate('schedules.schedule_date', $validated['source_date'])
            ->select('schedules.*');

        if ($user->role === 'teacher') {
            $sourceQuery->where('classes.teacher_id', $user->id);
        } elseif ($user->role !== 'admin') {
            abort(403);
        }

        $sourceSchedules = $sourceQuery->get();

        if ($sourceSchedules->isEmpty()) {
            return back()->with('error', 'Không có lịch học nào trong ngày nguồn để sao chép.');
        }

        $copied = 0;
        $skipped = 0;

        DB::transaction(function () use ($sourceSchedules, $validated, &$copied, &$skipped) {
            foreach ($sourceSchedules as $schedule) {
                $isDuplicate = DB::table('schedules')
                    ->where('class_id', $schedule->class_id)
                    ->where('course_id', $schedule->course_id)
                    ->whereDate('schedule_date', $validated['target_date'])
                    ->where('start_time', $schedule->start_time)
                    ->where('end_time', $schedule->end_time)
                    ->where(function ($query) use ($schedule) {
                        if ($schedule->room === null) {
                            $query->whereNull('room');
                        } else {
                            $query->where('room', $schedule->room);
                        }
                    })
                    ->exists();

                if ($isDuplicate) {
                    $skipped++;
                    continue;
                }

                Schedule::create([
                    'class_id' => $schedule->class_id,
                    'course_id' => $schedule->course_id,
                    'schedule_date' => $validated['target_date'],
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'room' => $schedule->room,
                    'note' => null,
                ]);

                $copied++;
            }
        });

        if ($copied === 0) {
            return back()->with('error', "Tất cả lịch trong ngày đích đã tồn tại, không có buổi học mới được sao chép.");
        }

        $message = "Đã sao chép {$copied} buổi học sang ngày mới.";
        if ($skipped > 0) {
            $message .= " Bỏ qua {$skipped} lịch bị trùng.";
        }

        return back()->with('success', $message);
    }

    public function importExcel(Request $request)
    {
        $validated = $request->validate([
            'import_class_id' => 'nullable|exists:classes,id',
            'default_course_id' => 'nullable|exists:courses,id',
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $user = auth()->user();

        if ($user->role !== 'admin' && $user->role !== 'teacher') {
            abort(403);
        }

        $allowedClassIds = [];
        if ($user->role === 'teacher') {
            $allowedClassIds = Classroom::where('teacher_id', $user->id)->pluck('id')->all();
        }

        if (!empty($validated['import_class_id'])) {
            $classroom = Classroom::with('courses')->findOrFail($validated['import_class_id']);

            if ($user->role === 'teacher' && !in_array($classroom->id, $allowedClassIds, true)) {
                return back()->with('error', 'Bạn không có quyền nhập lịch cho lớp này.');
            }

            if (!empty($validated['default_course_id']) && !$classroom->courses->contains('id', (int) $validated['default_course_id'])) {
                return back()->with('error', 'Khóa học mặc định không thuộc lớp đã chọn.');
            }
        }

        try {
            $import = new ScheduleImport(
                isset($validated['import_class_id']) ? (int) $validated['import_class_id'] : null,
                isset($validated['default_course_id']) ? (int) $validated['default_course_id'] : null,
                $allowedClassIds
            );

            Excel::import($import, $request->file('file'));

            if ($import->importedCount === 0 && $import->duplicateCount === 0 && $import->invalidCount === 0) {
                return back()->with('error', 'Không tìm thấy dữ liệu lịch học hợp lệ trong file Excel.');
            }

            $message = "Đã nhập {$import->importedCount} buổi học.";
            if ($import->duplicateCount > 0) {
                $message .= " Bỏ qua {$import->duplicateCount} lịch trùng.";
            }
            if ($import->invalidCount > 0) {
                $message .= " Có {$import->invalidCount} dòng chưa nhập được do thiếu dữ liệu hoặc không khớp khóa học.";
            }

            $unmatchedClasses = collect($import->unmatchedClasses)->unique()->take(3)->values();
            if ($unmatchedClasses->isNotEmpty()) {
                $message .= ' Lớp chưa khớp: ' . $unmatchedClasses->implode(', ') . '.';
            }

            $unmatchedSubjects = collect($import->unmatchedSubjects)->unique()->take(3)->values();
            if ($unmatchedSubjects->isNotEmpty()) {
                $message .= ' Môn chưa khớp: ' . $unmatchedSubjects->implode(', ') . '.';
            }

            return back()->with($import->importedCount > 0 ? 'success' : 'error', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi khi nhập lịch: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'class_id' => 'required',
            'course_id' => 'required',
            'schedule_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'note' => 'nullable|string|max:255',
        ]);

        $schedule = Schedule::findOrFail($id);
        $this->clearCourseExamNoteIfNeeded($request, $schedule->id);
        $schedule->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Đã cập nhật lịch!']);
    }

    public function destroy($id)
    {
        Schedule::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Đã xóa lịch!']);
    }

    private function clearCourseExamNoteIfNeeded(Request $request, ?int $exceptScheduleId = null): void
    {
        if ($request->input('note') !== 'Thi kết thúc môn') {
            return;
        }

        Schedule::query()
            ->where('class_id', $request->input('class_id'))
            ->where('course_id', $request->input('course_id'))
            ->when($exceptScheduleId, fn ($query) => $query->where('id', '!=', $exceptScheduleId))
            ->where('note', 'Thi kết thúc môn')
            ->update(['note' => null]);
    }
}
