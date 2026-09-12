<?php

namespace App\Http\Controllers;

use App\Imports\ScheduleImport;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\ScheduleAdjustmentBatch;
use App\Models\ScheduleChangeBatch;
use App\Models\User;
use App\Rules\SafeSpreadsheet;
use App\Services\AuditLogger;
use App\Services\NotificationCenter;
use App\Services\ScheduleConflictService;
use App\Services\ScheduleQuickUndoService;
use App\Services\ScheduleRecurrenceService;
use App\Services\ScheduleWriteLockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleConflictService $scheduleConflicts,
        private ScheduleRecurrenceService $scheduleRecurrence,
        private ScheduleWriteLockService $scheduleWriteLocks,
        private ScheduleQuickUndoService $scheduleQuickUndo,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'student') {
            abort(403, 'Bạn chỉ có thể xem lịch học tại trang tổng quan.');
        }

        if ($request->ajax() || $request->wantsJson() || $request->has('start')) {
            [$rangeStart, $rangeEnd] = $this->calendarRange($request);
            $filters = $this->calendarFilters($request);

            // SỬ DỤNG DB JOIN ĐỂ TRÁNH LỖI MODEL RELATIONSHIP
            $query = DB::table('schedules')
                ->join('courses', 'schedules.course_id', '=', 'courses.id')
                ->join('classes', 'schedules.class_id', '=', 'classes.id')
                ->where('schedules.status', 'active')
                ->where('classes.status', '!=', 'archived')
                ->where('courses.status', '!=', 'archived')
                ->where('schedules.schedule_date', '>=', $rangeStart)
                ->where('schedules.schedule_date', '<', $rangeEnd)
                ->when($filters['class_id'] ?? null, fn ($query, $classId) => $query->where('schedules.class_id', $classId))
                ->when($filters['course_id'] ?? null, fn ($query, $courseId) => $query->where('schedules.course_id', $courseId))
                ->when($filters['teacher_id'] ?? null, fn ($query, $teacherId) => $query->where('classes.teacher_id', $teacherId))
                ->when($filters['room'] ?? null, fn ($query, $room) => $query->whereRaw(
                    'LOWER(TRIM(schedules.room)) = ?',
                    [mb_strtolower(trim($room))]
                ))
                ->when(($filters['kind'] ?? null) === 'exam', fn ($query) => $query->whereNotNull('schedules.note')->where('schedules.note', '!=', ''))
                ->when(($filters['kind'] ?? null) === 'regular', fn ($query) => $query->where(fn ($noteQuery) => $noteQuery
                    ->whereNull('schedules.note')
                    ->orWhere('schedules.note', '')))
                ->select('schedules.*', 'courses.title as course_title', 'classes.name as class_name');

            // Nếu là giáo viên, chỉ lấy lịch của họ
            if ($user->role === 'teacher') {
                $query->where('classes.teacher_id', $user->id);
            }

            $schedules = $query->get();
            $events = [];

            foreach ($schedules as $schedule) {
                // SỬ DỤNG CARBON ĐỂ CHUẨN HÓA CHUỖI NGÀY GIỜ ISO8601
                $date = Carbon::parse($schedule->schedule_date)->format('Y-m-d');
                $startTime = Carbon::parse($schedule->start_time)->format('H:i:s');
                $endTime = Carbon::parse($schedule->end_time)->format('H:i:s');
                $hasExamNote = trim((string) ($schedule->note ?? '')) !== '';

                $events[] = [
                    'id' => $schedule->id,
                    'title' => $schedule->course_title.' ('.$schedule->class_name.')'.($hasExamNote ? ' - '.$schedule->note : ''),
                    'start' => $date.'T'.$startTime,
                    'end' => $date.'T'.$endTime,
                    'extendedProps' => [
                        'class_id' => $schedule->class_id,
                        'course_id' => $schedule->course_id,
                        'room' => $schedule->room,
                        'note' => $schedule->note,
                        'series_id' => $schedule->series_id ?? null,
                        'series_position' => $schedule->series_position ?? null,
                    ],
                    'backgroundColor' => $hasExamNote ? '#dc2626' : '#0d6efd',
                    'borderColor' => $hasExamNote ? '#dc2626' : '#0d6efd',
                ];
            }

            return response()->json($events);
        }

        // CHỈ LOAD DANH SÁCH LỚP HỌC (Khóa học sẽ load sau bằng AJAX)
        if ($user->role === 'teacher') {
            $classes = DB::table('classes')->where('teacher_id', $user->id)->where('status', '!=', 'archived')->get();
        } else {
            $classes = DB::table('classes')->where('status', '!=', 'archived')->get();
        }

        $classIds = $classes->pluck('id');
        $bulkCourses = DB::table('courses')
            ->join('class_course', 'courses.id', '=', 'class_course.course_id')
            ->whereIn('class_course.class_id', $classIds)
            ->where('courses.status', '!=', Course::STATUS_ARCHIVED)
            ->select('courses.id', 'courses.title')
            ->distinct()
            ->orderBy('courses.title')
            ->get();
        $recentAdjustments = ScheduleAdjustmentBatch::query()
            ->with('creator:id,name')
            ->when($user->isTeacher(), fn ($query) => $query->where('created_by', $user->id))
            ->latest()
            ->limit(8)
            ->get();
        $calendarTeachers = $user->isAdmin()
            ? User::query()->where('role', User::ROLE_TEACHER)->orderBy('name')->get(['id', 'name'])
            : collect([$user]);
        $calendarRooms = Schedule::query()
            ->where('status', Schedule::STATUS_ACTIVE)
            ->whereNotNull('room')
            ->when($user->isTeacher(), fn ($query) => $query->whereHas(
                'classroom',
                fn ($classQuery) => $classQuery->where('teacher_id', $user->id)
            ))
            ->select('room')
            ->distinct()
            ->orderBy('room')
            ->pluck('room');

        return view('schedules.index', compact(
            'classes',
            'bulkCourses',
            'recentAdjustments',
            'calendarTeachers',
            'calendarRooms'
        ));
    }

    // HÀM MỚI: Lấy danh sách khóa học thuộc về 1 lớp cụ thể
    public function getCoursesByClass($class_id)
    {
        $classroom = Classroom::findOrFail($class_id);
        Gate::authorize('view', $classroom);

        $courses = DB::table('class_course')
            ->join('courses', 'class_course.course_id', '=', 'courses.id')
            ->where('class_course.class_id', $class_id)
            ->where('courses.status', '!=', 'archived')
            ->select('courses.id', 'courses.title')
            ->get();

        return response()->json($courses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'required|exists:courses,id',
            'schedule_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
        ]);

        $classroom = Classroom::findOrFail($validated['class_id']);
        $course = Course::findOrFail($validated['course_id']);
        Gate::authorize('create', [Schedule::class, $classroom, $course]);

        $scheduleData = array_merge($validated, [
            'room' => $this->normalizeRoom($validated['room'] ?? null),
            'status' => Schedule::STATUS_ACTIVE,
        ]);

        $schedule = DB::transaction(function () use ($scheduleData, $classroom) {
            $this->scheduleWriteLocks->acquire([$scheduleData]);
            $this->scheduleConflicts->ensureNoConflicts($scheduleData, null, $classroom);
            $this->clearCourseExamNoteIfNeeded($scheduleData);

            return Schedule::create($scheduleData);
        });
        $this->notifyScheduleChange($schedule, 'Lịch học mới', 'Lịch học mới đã được thêm.');

        AuditLogger::log(
            AuditLogger::SCHEDULE_CREATED,
            $schedule,
            null,
            AuditLogger::snapshot($schedule),
            ['class_id' => $schedule->class_id, 'course_id' => $schedule->course_id],
            'Tạo lịch học.'
        );

        return response()->json(['status' => 'success', 'message' => 'Đã thêm lịch học!']);
    }

    public function previewSeries(Request $request)
    {
        $validated = $this->validateSeriesRequest($request);
        [$scheduleData, $recurrenceData, $classroom] = $this->prepareSeriesData($validated);
        $preview = $this->scheduleRecurrence->preview($scheduleData, $recurrenceData, $classroom);
        $conflictCount = collect($preview)->where('has_conflict', true)->count();

        return response()->json([
            'occurrences' => $preview,
            'summary' => [
                'total' => count($preview),
                'available' => count($preview) - $conflictCount,
                'conflicts' => $conflictCount,
            ],
        ]);
    }

    public function storeSeries(Request $request)
    {
        $validated = $this->validateSeriesRequest($request);
        [$scheduleData, $recurrenceData, $classroom] = $this->prepareSeriesData($validated);
        $dates = $this->scheduleRecurrence->dates(array_merge($scheduleData, $recurrenceData));
        $skipConflicts = (bool) ($validated['skip_conflicts'] ?? false);
        $seriesId = (string) Str::uuid();
        $skipped = 0;

        $createdSchedules = DB::transaction(function () use (
            $dates,
            $scheduleData,
            $classroom,
            $skipConflicts,
            $seriesId,
            &$skipped
        ) {
            $created = collect();
            $candidates = collect($dates)->map(fn ($date) => array_merge($scheduleData, [
                'schedule_date' => $date->toDateString(),
            ]))->all();
            $this->scheduleWriteLocks->acquire($candidates);
            $conflictsByDate = $this->scheduleConflicts->conflictsForDates(
                $scheduleData,
                $dates,
                null,
                $classroom
            );

            foreach ($dates as $position => $date) {
                $candidate = array_merge($scheduleData, [
                    'schedule_date' => $date->toDateString(),
                    'series_id' => $seriesId,
                    'series_position' => $position + 1,
                ]);
                $conflicts = $conflictsByDate[$date->toDateString()] ?? [];

                if ($conflicts !== []) {
                    if ($skipConflicts) {
                        $skipped++;

                        continue;
                    }

                    throw ValidationException::withMessages([
                        'schedule' => 'Buổi '.($position + 1).' ngày '.$date->format('d/m/Y').' bị trùng lịch: '.implode(' ', $conflicts),
                    ]);
                }

                $created->push(Schedule::create($candidate));
            }

            if ($created->isEmpty()) {
                throw ValidationException::withMessages([
                    'schedule' => 'Tất cả các buổi trong chuỗi đều bị trùng. Không có lịch nào được tạo.',
                ]);
            }

            return $created;
        });

        $this->notifySeriesChange(
            $createdSchedules->first(),
            $createdSchedules->count(),
            'Chuỗi lịch học mới',
            'Một chuỗi lịch học mới đã được thêm.'
        );

        AuditLogger::log(
            AuditLogger::SCHEDULE_SERIES_CREATED,
            $createdSchedules->first(),
            null,
            [
                'series_id' => $seriesId,
                'created_count' => $createdSchedules->count(),
                'skipped_count' => $skipped,
                'first_date' => $createdSchedules->min('schedule_date')?->format('Y-m-d'),
                'last_date' => $createdSchedules->max('schedule_date')?->format('Y-m-d'),
            ],
            ['class_id' => $scheduleData['class_id'], 'course_id' => $scheduleData['course_id']],
            'Tạo chuỗi lịch học lặp lại.'
        );

        $message = 'Đã tạo '.$createdSchedules->count().' buổi học trong chuỗi.';
        if ($skipped > 0) {
            $message .= " Đã bỏ qua {$skipped} buổi bị trùng.";
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'series_id' => $seriesId,
            'created_count' => $createdSchedules->count(),
            'skipped_count' => $skipped,
        ]);
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
        $sourceQuery = Schedule::query()
            ->with(['classroom', 'course'])
            ->whereDate('schedule_date', $validated['source_date'])
            ->where('status', Schedule::STATUS_ACTIVE)
            ->whereHas('classroom', fn ($query) => $query->where('status', '!=', Classroom::STATUS_ARCHIVED));

        if ($user->role === 'teacher') {
            $sourceQuery->whereHas('classroom', fn ($query) => $query->where('teacher_id', $user->id));
        } elseif ($user->role !== 'admin') {
            abort(403);
        }

        $sourceSchedules = $sourceQuery->get();

        if ($sourceSchedules->isEmpty()) {
            return back()->with('error', 'Không có lịch học nào trong ngày nguồn để sao chép.');
        }

        $copied = 0;
        $skipped = 0;
        $copiedSchedules = collect();

        DB::transaction(function () use ($sourceSchedules, $validated, &$copied, &$skipped, $copiedSchedules) {
            $candidates = $sourceSchedules->map(function (Schedule $schedule) use ($validated): array {
                Gate::authorize('create', [Schedule::class, $schedule->classroom, $schedule->course]);

                return [
                    'class_id' => $schedule->class_id,
                    'course_id' => $schedule->course_id,
                    'schedule_date' => $validated['target_date'],
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'room' => $schedule->room,
                    'note' => null,
                    'status' => Schedule::STATUS_ACTIVE,
                ];
            })->values();
            $this->scheduleWriteLocks->acquire($candidates->all());

            foreach ($sourceSchedules as $index => $schedule) {
                $scheduleData = $candidates[$index];

                if ($this->scheduleConflicts->conflicts($scheduleData, null, $schedule->classroom) !== []) {
                    $skipped++;

                    continue;
                }

                $copiedSchedules->push(Schedule::create($scheduleData));

                $copied++;
            }
        });

        $copiedSchedules->groupBy('class_id')->each(function ($classSchedules, $classId) use ($validated): void {
            app(NotificationCenter::class)->notifyClassStudents(
                (int) $classId,
                'schedule',
                'Lịch học mới đã được sao chép',
                "Có {$classSchedules->count()} buổi học mới vào ngày ".Carbon::parse($validated['target_date'])->format('d/m/Y').'.',
                route('students.schedule'),
                ['schedule_ids' => $classSchedules->pluck('id')->all()],
                'schedule:copy:'.Str::uuid()
            );
        });

        AuditLogger::log(
            AuditLogger::SCHEDULE_COPIED,
            null,
            null,
            [
                'copied_count' => $copied,
                'skipped_count' => $skipped,
            ],
            [
                'source_date' => $validated['source_date'],
                'target_date' => $validated['target_date'],
            ],
            'Sao chép lịch học sang ngày mới.'
        );

        if ($copied === 0) {
            return back()->with('error', 'Tất cả lịch trong ngày đích đã tồn tại, không có buổi học mới được sao chép.');
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
            'file' => ['required', 'file', 'max:5120', new SafeSpreadsheet],
        ]);

        $user = auth()->user();

        if ($user->role !== 'admin' && $user->role !== 'teacher') {
            abort(403);
        }

        $allowedClassIds = null;
        if ($user->role === 'teacher') {
            $allowedClassIds = Classroom::where('teacher_id', $user->id)->pluck('id')->all();
        }

        if (! empty($validated['import_class_id'])) {
            $classroom = Classroom::with('courses')->findOrFail($validated['import_class_id']);

            if ($user->role === 'teacher' && ! in_array($classroom->id, $allowedClassIds, true)) {
                return back()->with('error', 'Bạn không có quyền nhập lịch cho lớp này.');
            }

            if (! empty($validated['default_course_id']) && ! $classroom->courses->contains('id', (int) $validated['default_course_id'])) {
                return back()->with('error', 'Khóa học mặc định không thuộc lớp đã chọn.');
            }
        }

        try {
            $import = new ScheduleImport(
                isset($validated['import_class_id']) ? (int) $validated['import_class_id'] : null,
                isset($validated['default_course_id']) ? (int) $validated['default_course_id'] : null,
                $allowedClassIds,
                $this->scheduleConflicts,
                $this->scheduleWriteLocks,
            );

            Excel::import($import, $request->file('file'));

            if ($import->importedCount === 0 && $import->duplicateCount === 0 && $import->invalidCount === 0 && $import->conflictCount === 0) {
                return back()->with('error', 'Không tìm thấy dữ liệu lịch học hợp lệ trong file Excel.');
            }

            $message = "Đã nhập {$import->importedCount} buổi học.";
            if ($import->duplicateCount > 0) {
                $message .= " Bỏ qua {$import->duplicateCount} lịch trùng.";
            }
            if ($import->invalidCount > 0) {
                $message .= " Có {$import->invalidCount} dòng chưa nhập được do thiếu dữ liệu hoặc không khớp khóa học.";
            }
            if ($import->conflictCount > 0) {
                $message .= " Bỏ qua {$import->conflictCount} lịch bị trùng giờ lớp, giáo viên hoặc phòng.";
            }

            $unmatchedClasses = collect($import->unmatchedClasses)->unique()->take(3)->values();
            if ($unmatchedClasses->isNotEmpty()) {
                $message .= ' Lớp chưa khớp: '.$unmatchedClasses->implode(', ').'.';
            }

            $unmatchedSubjects = collect($import->unmatchedSubjects)->unique()->take(3)->values();
            if ($unmatchedSubjects->isNotEmpty()) {
                $message .= ' Môn chưa khớp: '.$unmatchedSubjects->implode(', ').'.';
            }

            AuditLogger::log(
                AuditLogger::SCHEDULE_IMPORTED,
                null,
                null,
                [
                    'imported_count' => $import->importedCount,
                    'duplicate_count' => $import->duplicateCount,
                    'invalid_count' => $import->invalidCount,
                    'conflict_count' => $import->conflictCount,
                    'unmatched_classes' => collect($import->unmatchedClasses)->unique()->values()->all(),
                    'unmatched_subjects' => collect($import->unmatchedSubjects)->unique()->values()->all(),
                ],
                [
                    'file_name' => $request->file('file')->getClientOriginalName(),
                    'import_class_id' => $validated['import_class_id'] ?? null,
                    'default_course_id' => $validated['default_course_id'] ?? null,
                ],
                'Import lịch học từ Excel.'
            );

            collect($import->importedByClass)->each(function ($count, $classId): void {
                app(NotificationCenter::class)->notifyClassStudents(
                    (int) $classId,
                    'schedule',
                    'Lịch học mới đã được nhập',
                    "Có {$count} buổi học mới được thêm từ file lịch.",
                    route('students.schedule'),
                    ['imported_count' => $count],
                    'schedule:import:'.Str::uuid()
                );
            });

            return back()->with($import->importedCount > 0 ? 'success' : 'error', $message);
        } catch (\Exception $e) {
            report($e);

            return back()->with('error', 'Không thể nhập lịch lúc này. Vui lòng kiểm tra file và thử lại.');
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'required|exists:courses,id',
            'schedule_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,hidden,archived',
            'update_scope' => 'nullable|in:occurrence,future,series',
            'mutation_source' => 'nullable|in:calendar_drag',
        ]);

        $updateScope = $validated['update_scope'] ?? 'occurrence';
        $recordQuickUndo = ($validated['mutation_source'] ?? null) === 'calendar_drag';
        unset($validated['update_scope'], $validated['mutation_source']);
        $schedule = Schedule::with(['classroom', 'course'])->findOrFail($id);
        Gate::authorize('update', $schedule);
        $targetClassroom = Classroom::findOrFail($validated['class_id']);
        $targetCourse = Course::findOrFail($validated['course_id']);
        Gate::authorize('create', [Schedule::class, $targetClassroom, $targetCourse]);

        if ($updateScope !== 'occurrence') {
            return $this->updateSeries(
                $schedule,
                $validated,
                $targetClassroom,
                $updateScope,
                $recordQuickUndo,
                $request->user()
            );
        }

        $result = DB::transaction(function () use ($id, $validated, $targetClassroom, $recordQuickUndo, $request): array {
            $schedule = Schedule::with(['classroom', 'course'])->lockForUpdate()->findOrFail($id);
            Gate::authorize('update', $schedule);
            $oldValues = AuditLogger::snapshot($schedule);
            $before = $this->scheduleQuickUndo->snapshots([$schedule]);
            $scheduleData = array_merge($validated, [
                'room' => $this->normalizeRoom($validated['room'] ?? null),
                'status' => $validated['status'] ?? $schedule->status ?? Schedule::STATUS_ACTIVE,
            ]);
            $this->scheduleWriteLocks->acquire(array_merge($before, [$scheduleData]));
            $this->scheduleConflicts->ensureNoConflicts($scheduleData, $schedule->id, $targetClassroom);
            $this->clearCourseExamNoteIfNeeded($scheduleData, $schedule->id);
            $schedule->update($scheduleData);

            $after = $this->scheduleQuickUndo->snapshots([$schedule->fresh()]);
            $change = $recordQuickUndo
                ? $this->scheduleQuickUndo->record($request->user(), 'occurrence', $before, $after)
                : null;

            return compact('schedule', 'oldValues', 'change');
        });
        /** @var Schedule $schedule */
        $schedule = $result['schedule'];
        $oldValues = $result['oldValues'];

        if ((int) $oldValues['class_id'] !== (int) $schedule->class_id) {
            $this->notifyPreviousClassOfMove((int) $oldValues['class_id'], $schedule, $oldValues);
        }

        $this->notifyScheduleChange($schedule, 'Lịch học đã thay đổi', 'Giáo viên vừa cập nhật thời gian hoặc thông tin buổi học.');

        AuditLogger::log(
            AuditLogger::SCHEDULE_UPDATED,
            $schedule,
            $oldValues,
            AuditLogger::snapshot($schedule->fresh()),
            [
                'class_id' => $schedule->class_id,
                'course_id' => $schedule->course_id,
            ],
            'Cập nhật lịch học.'
        );

        return response()->json(array_filter([
            'status' => 'success',
            'message' => 'Đã cập nhật lịch!',
            'undo_url' => $result['change'] ? route('schedules.changes.undo', $result['change']) : null,
            'undo_expires_at' => $result['change']?->expires_at?->toIso8601String(),
        ]));
    }

    public function undoQuickChange(Request $request, ScheduleChangeBatch $change)
    {
        $batch = $this->scheduleQuickUndo->undo($request->user(), $change);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã hoàn tác '.$batch->schedule_count.' buổi học về thời gian trước đó.',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $validated = $request->validate([
            'delete_scope' => 'nullable|in:occurrence,future,series',
        ]);
        $schedule = Schedule::with(['classroom', 'course'])->findOrFail($id);
        Gate::authorize('delete', $schedule);

        $deleteScope = $validated['delete_scope'] ?? 'occurrence';
        if ($deleteScope !== 'occurrence') {
            return $this->archiveSeries($schedule, $deleteScope);
        }

        [$schedule, $oldValues] = DB::transaction(function () use ($id): array {
            $schedule = Schedule::with(['classroom', 'course'])->lockForUpdate()->findOrFail($id);
            Gate::authorize('delete', $schedule);
            $oldValues = AuditLogger::snapshot($schedule);
            $schedule->update(['status' => Schedule::STATUS_ARCHIVED]);

            return [$schedule, $oldValues];
        });

        $this->notifyScheduleChange($schedule, 'Buổi học đã hủy', 'Một buổi học trong lịch của bạn đã được hủy.');

        AuditLogger::log(
            AuditLogger::SCHEDULE_ARCHIVED,
            $schedule,
            $oldValues,
            AuditLogger::snapshot($schedule->fresh()),
            [
                'class_id' => $schedule->class_id,
                'course_id' => $schedule->course_id,
            ],
            'Lưu trữ lịch học.'
        );

        return response()->json(['status' => 'success', 'message' => 'Đã lưu trữ lịch học!']);
    }

    private function updateSeries(
        Schedule $schedule,
        array $validated,
        Classroom $targetClassroom,
        string $updateScope,
        bool $recordQuickUndo,
        User $user
    ) {
        if (! $schedule->series_id) {
            throw ValidationException::withMessages([
                'update_scope' => 'Buổi học này không thuộc chuỗi lịch.',
            ]);
        }

        $originalSeriesId = $schedule->series_id;
        $result = DB::transaction(function () use (
            $schedule,
            $validated,
            $targetClassroom,
            $updateScope,
            $recordQuickUndo,
            $user
        ): array {
            $selected = Schedule::query()->lockForUpdate()->findOrFail($schedule->id);
            $members = Schedule::query()
                ->with(['classroom', 'course'])
                ->where('series_id', $selected->series_id)
                ->notArchived()
                ->when($updateScope === 'future', function ($query) use ($selected): void {
                    $selected->series_position !== null
                        ? $query->where('series_position', '>=', $selected->series_position)
                        : $query->where('schedule_date', '>=', $selected->schedule_date->toDateString());
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $members->each(fn (Schedule $member) => Gate::forUser($user)->authorize('update', $member));

            if ($members->count() > 1 && ($validated['note'] ?? null) === 'Thi kết thúc môn') {
                throw ValidationException::withMessages([
                    'note' => 'Không thể đánh dấu nhiều buổi trong chuỗi là lịch thi kết thúc môn.',
                ]);
            }

            $dayShift = (int) Carbon::parse($selected->schedule_date)
                ->startOfDay()
                ->diffInDays(Carbon::parse($validated['schedule_date'])->startOfDay(), false);
            $memberIds = $members->modelKeys();
            $baseData = array_merge($validated, [
                'room' => $this->normalizeRoom($validated['room'] ?? null),
                'status' => $validated['status'] ?? $selected->status ?? Schedule::STATUS_ACTIVE,
            ]);
            $newSeriesId = $updateScope === 'future' ? (string) Str::uuid() : $selected->series_id;
            $orderedMembers = $members->sortBy(fn (Schedule $member) => [
                $member->series_position ?? PHP_INT_MAX,
                $member->schedule_date->format('Y-m-d'),
                $member->id,
            ])->values();
            $updates = $orderedMembers->mapWithKeys(function (Schedule $member, int $index) use (
                $baseData,
                $dayShift,
                $updateScope,
                $newSeriesId
            ): array {
                return [$member->id => array_merge($baseData, [
                    'schedule_date' => $member->schedule_date->copy()->addDays($dayShift)->toDateString(),
                    ...($updateScope === 'future' ? [
                        'series_id' => $newSeriesId,
                        'series_position' => $index + 1,
                    ] : []),
                ])];
            });
            $before = $this->scheduleQuickUndo->snapshots($members);
            $this->scheduleWriteLocks->acquire(array_merge($before, $updates->values()->all()));
            $conflictsByDate = $this->scheduleConflicts->conflictsForDates(
                $baseData,
                $updates->pluck('schedule_date')->all(),
                $memberIds,
                $targetClassroom
            );

            foreach ($members as $member) {
                $candidateDate = $updates[$member->id]['schedule_date'];
                $conflicts = $conflictsByDate[$candidateDate] ?? [];

                if ($conflicts !== []) {
                    throw ValidationException::withMessages([
                        'schedule' => 'Buổi số '.$member->series_position.' ngày '
                            .Carbon::parse($candidateDate)->format('d/m/Y')
                            .' bị trùng lịch: '.implode(' ', $conflicts),
                    ]);
                }
            }

            $oldClassIds = $members->pluck('class_id')->map(fn ($id) => (int) $id)->unique()->values();
            $oldAuditSnapshot = $members->map(fn (Schedule $member) => AuditLogger::snapshot($member))->all();
            foreach ($members as $member) {
                $member->update($updates[$member->id]);
            }
            $freshMembers = Schedule::query()->whereKey($memberIds)->orderBy('id')->get();
            $after = $this->scheduleQuickUndo->snapshots($freshMembers);
            $change = $recordQuickUndo
                ? $this->scheduleQuickUndo->record($user, $updateScope, $before, $after)
                : null;

            return compact('freshMembers', 'oldClassIds', 'oldAuditSnapshot', 'change', 'newSeriesId');
        });

        $members = $result['freshMembers'];
        $oldClassIds = $result['oldClassIds'];
        $oldSnapshot = $result['oldAuditSnapshot'];
        $freshFirst = $members->first();
        $oldClassIds
            ->reject(fn (int $classId) => $classId === (int) $freshFirst->class_id)
            ->each(fn (int $classId) => $this->notifyArchivedOrMovedSeries($classId, $members->count(), false));
        $this->notifySeriesChange(
            $freshFirst,
            $members->count(),
            'Chuỗi lịch học đã thay đổi',
            'Giáo viên vừa cập nhật thông tin của cả chuỗi.'
        );

        AuditLogger::log(
            AuditLogger::SCHEDULE_SERIES_UPDATED,
            $freshFirst,
            ['members' => $oldSnapshot],
            ['members' => $members->map(fn (Schedule $member) => AuditLogger::snapshot($member))->all()],
            [
                'series_id' => $originalSeriesId,
                'new_series_id' => $result['newSeriesId'],
                'scope' => $updateScope,
                'updated_count' => $members->count(),
            ],
            $updateScope === 'future'
                ? 'Cập nhật buổi đã chọn và các buổi sau trong chuỗi lịch.'
                : 'Cập nhật toàn bộ chuỗi lịch học.'
        );

        return response()->json(array_filter([
            'status' => 'success',
            'message' => 'Đã cập nhật '.$members->count().' buổi trong chuỗi lịch!',
            'updated_count' => $members->count(),
            'undo_url' => $result['change'] ? route('schedules.changes.undo', $result['change']) : null,
            'undo_expires_at' => $result['change']?->expires_at?->toIso8601String(),
        ]));
    }

    private function archiveSeries(Schedule $schedule, string $deleteScope)
    {
        if (! $schedule->series_id) {
            throw ValidationException::withMessages([
                'delete_scope' => 'Buổi học này không thuộc chuỗi lịch.',
            ]);
        }

        [$members, $oldSnapshot] = DB::transaction(function () use ($schedule, $deleteScope): array {
            $selected = Schedule::query()->lockForUpdate()->findOrFail($schedule->id);
            $members = Schedule::query()
                ->with(['classroom', 'course'])
                ->where('series_id', $selected->series_id)
                ->notArchived()
                ->when($deleteScope === 'future', function ($query) use ($selected): void {
                    $selected->series_position !== null
                        ? $query->where('series_position', '>=', $selected->series_position)
                        : $query->where('schedule_date', '>=', $selected->schedule_date->toDateString());
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $members->each(fn (Schedule $member) => Gate::authorize('delete', $member));
            $oldSnapshot = $members->map(fn (Schedule $member) => AuditLogger::snapshot($member))->all();
            Schedule::query()->whereKey($members->modelKeys())->update(['status' => Schedule::STATUS_ARCHIVED]);

            return [$members, $oldSnapshot];
        });

        $members->groupBy('class_id')->each(
            fn ($classMembers, $classId) => $this->notifyArchivedOrMovedSeries((int) $classId, $classMembers->count(), true)
        );

        AuditLogger::log(
            AuditLogger::SCHEDULE_SERIES_ARCHIVED,
            $schedule,
            ['members' => $oldSnapshot],
            ['status' => Schedule::STATUS_ARCHIVED],
            [
                'series_id' => $schedule->series_id,
                'scope' => $deleteScope,
                'archived_count' => $members->count(),
            ],
            $deleteScope === 'future'
                ? 'Lưu trữ buổi đã chọn và các buổi sau trong chuỗi lịch.'
                : 'Lưu trữ toàn bộ chuỗi lịch học.'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Đã lưu trữ '.$members->count().' buổi trong chuỗi lịch!',
            'archived_count' => $members->count(),
        ]);
    }

    private function clearCourseExamNoteIfNeeded(array $attributes, ?int $exceptScheduleId = null): void
    {
        if (($attributes['note'] ?? null) !== 'Thi kết thúc môn') {
            return;
        }

        Schedule::query()
            ->where('class_id', $attributes['class_id'])
            ->where('course_id', $attributes['course_id'])
            ->notArchived()
            ->when($exceptScheduleId, fn ($query) => $query->where('id', '!=', $exceptScheduleId))
            ->where('note', 'Thi kết thúc môn')
            ->update(['note' => null]);
    }

    private function notifyScheduleChange(Schedule $schedule, string $title, string $message): void
    {
        $date = Carbon::parse($schedule->schedule_date)->format('d/m/Y');
        $time = Carbon::parse($schedule->start_time)->format('H:i');

        app(NotificationCenter::class)->notifyClassStudents(
            (int) $schedule->class_id,
            'schedule',
            $title,
            "{$message} Thời gian: {$time} ngày {$date}.",
            route('students.schedule'),
            ['schedule_id' => $schedule->id, 'course_id' => $schedule->course_id],
            'schedule:'.$schedule->id.':'.md5($title.'|'.json_encode([
                $schedule->class_id,
                $schedule->course_id,
                $schedule->schedule_date,
                $schedule->start_time,
                $schedule->end_time,
                $schedule->room,
                $schedule->note,
                $schedule->status,
            ]))
        );
    }

    private function notifyPreviousClassOfMove(int $oldClassId, Schedule $schedule, array $oldValues): void
    {
        app(NotificationCenter::class)->notifyClassStudents(
            $oldClassId,
            'schedule',
            'Lịch học đã chuyển sang lớp khác',
            'Một buổi học lúc '.Carbon::parse($oldValues['start_time'])->format('H:i').' ngày '
                .Carbon::parse($oldValues['schedule_date'])->format('d/m/Y').' không còn thuộc lớp của bạn.',
            route('students.schedule'),
            ['schedule_id' => $schedule->id, 'course_id' => $schedule->course_id],
            'schedule:'.$schedule->id.':moved-from:'.$oldClassId.':'.md5(json_encode($oldValues))
        );
    }

    private function notifySeriesChange(Schedule $schedule, int $count, string $title, string $message): void
    {
        $range = Schedule::query()
            ->where('series_id', $schedule->series_id)
            ->notArchived()
            ->selectRaw('MIN(schedule_date) as first_date, MAX(schedule_date) as last_date')
            ->first();
        $firstDate = Carbon::parse($range->first_date ?? $schedule->schedule_date)->format('d/m/Y');
        $lastDate = Carbon::parse($range->last_date ?? $schedule->schedule_date)->format('d/m/Y');

        app(NotificationCenter::class)->notifyClassStudents(
            (int) $schedule->class_id,
            'schedule',
            $title,
            "{$message} Gồm {$count} buổi, từ {$firstDate} đến {$lastDate}.",
            route('students.schedule'),
            ['series_id' => $schedule->series_id, 'schedule_count' => $count],
            'schedule-series:'.$schedule->series_id.':'.md5($title.'|'.$message.'|'.$count.'|'.$firstDate.'|'.$lastDate)
        );
    }

    private function notifyArchivedOrMovedSeries(int $classId, int $count, bool $archived): void
    {
        app(NotificationCenter::class)->notifyClassStudents(
            $classId,
            'schedule',
            $archived ? 'Chuỗi lịch học đã hủy' : 'Chuỗi lịch học đã chuyển lớp',
            $archived
                ? "{$count} buổi học trong một chuỗi lịch đã được hủy."
                : "{$count} buổi học trong một chuỗi lịch không còn thuộc lớp của bạn.",
            route('students.schedule'),
            ['schedule_count' => $count],
            'schedule-series:'.$classId.':'.($archived ? 'archived:' : 'moved:').Str::uuid()
        );
    }

    private function validateSeriesRequest(Request $request): array
    {
        return $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'required|exists:courses,id',
            'schedule_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
            'repeat_interval' => 'required|integer|in:1,2',
            'end_mode' => 'required|in:count,date',
            'occurrence_count' => 'nullable|required_if:end_mode,count|integer|min:2|max:'.ScheduleRecurrenceService::MAX_OCCURRENCES,
            'repeat_until' => 'nullable|required_if:end_mode,date|date_format:Y-m-d|after:schedule_date',
            'skip_conflicts' => 'nullable|boolean',
        ], [
            'occurrence_count.min' => 'Chuỗi lịch phải có ít nhất 2 buổi.',
            'occurrence_count.max' => 'Một chuỗi lịch không được vượt quá '.ScheduleRecurrenceService::MAX_OCCURRENCES.' buổi.',
            'repeat_until.after' => 'Ngày kết thúc phải sau ngày của buổi học đầu tiên.',
        ]);
    }

    /**
     * @return array{0: array, 1: array, 2: Classroom}
     */
    private function prepareSeriesData(array $validated): array
    {
        $classroom = Classroom::findOrFail($validated['class_id']);
        $course = Course::findOrFail($validated['course_id']);
        Gate::authorize('create', [Schedule::class, $classroom, $course]);

        if (trim((string) ($validated['note'] ?? '')) !== '') {
            throw ValidationException::withMessages([
                'note' => 'Lịch thi kết thúc môn chỉ có thể tạo dưới dạng một buổi riêng lẻ.',
            ]);
        }

        $scheduleData = [
            'class_id' => (int) $validated['class_id'],
            'course_id' => (int) $validated['course_id'],
            'schedule_date' => $validated['schedule_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room' => $this->normalizeRoom($validated['room'] ?? null),
            'note' => null,
            'status' => Schedule::STATUS_ACTIVE,
        ];
        $recurrenceData = [
            'repeat_interval' => (int) $validated['repeat_interval'],
            'end_mode' => $validated['end_mode'],
            'occurrence_count' => isset($validated['occurrence_count']) ? (int) $validated['occurrence_count'] : null,
            'repeat_until' => $validated['repeat_until'] ?? null,
        ];

        return [$scheduleData, $recurrenceData, $classroom];
    }

    private function normalizeRoom(?string $room): ?string
    {
        $room = preg_replace('/\s+/', ' ', trim((string) $room));

        return $room !== '' ? $room : null;
    }

    private function calendarFilters(Request $request): array
    {
        return $request->validate([
            'class_id' => 'nullable|integer|exists:classes,id',
            'course_id' => 'nullable|integer|exists:courses,id',
            'teacher_id' => 'nullable|integer|exists:users,id',
            'room' => 'nullable|string|max:100',
            'kind' => 'nullable|in:regular,exam',
        ]);
    }

    /**
     * FullCalendar gửi `end` theo dạng mốc loại trừ (exclusive).
     *
     * @return array{0: string, 1: string}
     */
    private function calendarRange(Request $request): array
    {
        $validated = $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date|after:start',
        ]);

        $start = isset($validated['start'])
            ? Carbon::parse($validated['start'])->startOfDay()
            : now()->startOfMonth()->subMonth();
        $end = isset($validated['end'])
            ? Carbon::parse($validated['end'])->startOfDay()
            : now()->startOfMonth()->addMonths(2);

        if ($start->diffInDays($end) > 370) {
            throw ValidationException::withMessages([
                'range' => 'Khoảng thời gian xem lịch không được vượt quá 370 ngày.',
            ]);
        }

        return [$start->toDateString(), $end->toDateString()];
    }
}
