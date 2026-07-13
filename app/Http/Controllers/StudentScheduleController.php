<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudentScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'student') {
            abort(403, 'Trang này chỉ dành cho học sinh.');
        }

        $classIds = $user->classes()
            ->where('classes.status', 'active')
            ->pluck('classes.id');

        $courses = Course::visibleToStudents()
            ->whereHas('classes', function ($query) use ($classIds) {
                $query->where('classes.status', 'active')
                    ->whereIn('classes.id', $classIds);
            })
            ->orderBy('title')
            ->get(['id', 'title']);

        $courseIds = $courses->pluck('id');
        $selectedCourseId = $request->filled('course_id') && $courseIds->contains((int) $request->course_id)
            ? (int) $request->course_id
            : null;

        if ($request->ajax() || $request->wantsJson() || $request->has('start')) {
            return response()->json(
                $this->scheduleQuery($classIds, $courseIds, $selectedCourseId)
                    ->get()
                    ->map(fn ($schedule) => $this->calendarEvent($schedule))
                    ->values()
            );
        }

        $today = Carbon::today();
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $todaySchedules = $this->scheduleQuery($classIds, $courseIds, $selectedCourseId)
            ->whereDate('schedules.schedule_date', $today->toDateString())
            ->orderBy('schedules.start_time')
            ->get();

        $weekSchedules = $this->scheduleQuery($classIds, $courseIds, $selectedCourseId)
            ->whereDate('schedules.schedule_date', '>=', $today->toDateString())
            ->whereDate('schedules.schedule_date', '<=', $endOfWeek->toDateString())
            ->orderBy('schedules.schedule_date')
            ->orderBy('schedules.start_time')
            ->get();

        $upcomingSchedules = $this->scheduleQuery($classIds, $courseIds, $selectedCourseId)
            ->whereDate('schedules.schedule_date', '>=', $today->toDateString())
            ->orderBy('schedules.schedule_date')
            ->orderBy('schedules.start_time')
            ->limit(8)
            ->get();

        $examSchedules = $this->scheduleQuery($classIds, $courseIds, $selectedCourseId)
            ->whereNotNull('schedules.note')
            ->where('schedules.note', '!=', '')
            ->whereDate('schedules.schedule_date', '>=', $today->toDateString())
            ->orderBy('schedules.schedule_date')
            ->orderBy('schedules.start_time')
            ->limit(5)
            ->get();

        $filters = [
            'course_id' => $selectedCourseId,
        ];

        return view('students.schedule', compact(
            'courses',
            'todaySchedules',
            'weekSchedules',
            'upcomingSchedules',
            'examSchedules',
            'filters'
        ));
    }

    private function scheduleQuery($classIds, $courseIds, ?int $selectedCourseId = null)
    {
        return DB::table('schedules')
            ->join('courses', 'schedules.course_id', '=', 'courses.id')
            ->join('classes', 'schedules.class_id', '=', 'classes.id')
            ->whereIn('schedules.class_id', $classIds)
            ->whereIn('schedules.course_id', $courseIds)
            ->where('schedules.status', 'active')
            ->where('classes.status', 'active')
            ->where('courses.status', 'published')
            ->when($selectedCourseId, fn ($query) => $query->where('schedules.course_id', $selectedCourseId))
            ->select(
                'schedules.*',
                'courses.title as course_title',
                'classes.name as class_name'
            );
    }

    private function calendarEvent($schedule): array
    {
        $date = Carbon::parse($schedule->schedule_date)->format('Y-m-d');
        $startTime = Carbon::parse($schedule->start_time)->format('H:i:s');
        $endTime = Carbon::parse($schedule->end_time)->format('H:i:s');
        $hasNote = trim((string) ($schedule->note ?? '')) !== '';

        return [
            'id' => $schedule->id,
            'title' => $schedule->course_title.($hasNote ? ' - '.$schedule->note : ''),
            'start' => $date.'T'.$startTime,
            'end' => $date.'T'.$endTime,
            'backgroundColor' => $hasNote ? '#dc2626' : '#2563eb',
            'borderColor' => $hasNote ? '#dc2626' : '#2563eb',
            'extendedProps' => [
                'course' => $schedule->course_title,
                'class' => $schedule->class_name,
                'room' => $schedule->room,
                'note' => $schedule->note,
            ],
        ];
    }
}
