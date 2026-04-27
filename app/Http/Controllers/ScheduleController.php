<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

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

                $events[] = [
                    'id' => $schedule->id,
                    'title' => $schedule->course_title . ' (' . $schedule->class_name . ')',
                    'start' => $date . 'T' . $startTime,
                    'end' => $date . 'T' . $endTime,
                    'extendedProps' => [
                        'class_id' => $schedule->class_id,
                        'course_id' => $schedule->course_id,
                        'room' => $schedule->room,
                    ],
                    'backgroundColor' => '#0d6efd',
                    'borderColor' => '#0d6efd',
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
        ]);

        Schedule::create($request->all());
        return response()->json(['status' => 'success', 'message' => 'Đã thêm lịch học!']);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Đã cập nhật lịch!']);
    }

    public function destroy($id)
    {
        Schedule::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Đã xóa lịch!']);
    }
}
