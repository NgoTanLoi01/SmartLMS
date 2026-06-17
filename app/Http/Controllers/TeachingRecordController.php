<?php

namespace App\Http\Controllers;

use App\Imports\TeachingRecordsImport;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\TeachingRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TeachingRecordController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $user = auth()->user();
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'center_name' => $request->input('center_name'),
            'term_code' => $request->input('term_code'),
            'status' => $request->input('status'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ];

        $query = TeachingRecord::with(['teacher', 'course', 'classroom'])
            ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
            ->when($filters['search'], function ($q, $keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('subject_name', 'like', "%{$keyword}%")
                        ->orWhere('class_name', 'like', "%{$keyword}%")
                        ->orWhere('center_name', 'like', "%{$keyword}%")
                        ->orWhere('term_code', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['center_name'], fn ($q, $center) => $q->where('center_name', $center))
            ->when($filters['term_code'], fn ($q, $term) => $q->where('term_code', $term))
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($filters['from_date'], fn ($q, $date) => $q->whereDate('start_date', '>=', $date))
            ->when($filters['to_date'], fn ($q, $date) => $q->whereDate('start_date', '<=', $date));

        $records = $query
            ->orderByRaw('COALESCE(start_date, created_at) DESC')
            ->paginate(15)
            ->withQueryString();

        $scopeQuery = TeachingRecord::query()
            ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id));

        $stats = [
            'total_subjects' => (clone $scopeQuery)->count(),
            'total_sessions' => (clone $scopeQuery)->sum('planned_sessions'),
            'teaching' => (clone $scopeQuery)->where('status', TeachingRecord::STATUS_TEACHING)->count(),
            'completed' => (clone $scopeQuery)->where('status', TeachingRecord::STATUS_COMPLETED)->count(),
        ];

        $centers = TeachingRecord::query()
            ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
            ->whereNotNull('center_name')
            ->where('center_name', '!=', '')
            ->distinct()
            ->orderBy('center_name')
            ->pluck('center_name');

        $terms = TeachingRecord::query()
            ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
            ->whereNotNull('term_code')
            ->where('term_code', '!=', '')
            ->distinct()
            ->orderBy('term_code')
            ->pluck('term_code');

        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        $courses = Course::query()
            ->where('course_type', 'delivery')
            ->notArchived()
            ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
            ->orderBy('title')
            ->get();
        $classes = Classroom::query()
            ->notArchived()
            ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->id))
            ->orderBy('name')
            ->get();

        return view('teaching.index', compact(
            'records',
            'stats',
            'filters',
            'centers',
            'terms',
            'teachers',
            'courses',
            'classes'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $data = $this->validatedData($request);
        $data['teacher_id'] = auth()->user()->role === 'admin'
            ? (int) $data['teacher_id']
            : auth()->id();

        $data = $this->fillLinkedData($data);

        TeachingRecord::create($data);

        return back()->with('success', 'Đã thêm dòng giảng dạy thành công.');
    }

    public function update(Request $request, TeachingRecord $teaching)
    {
        $this->authorizeRecord($teaching);

        $data = $this->validatedData($request);
        $data['teacher_id'] = auth()->user()->role === 'admin'
            ? (int) $data['teacher_id']
            : $teaching->teacher_id;

        $data = $this->fillLinkedData($data);
        $teaching->update($data);

        return back()->with('success', 'Đã cập nhật dòng giảng dạy.');
    }

    public function destroy(TeachingRecord $teaching)
    {
        $this->authorizeRecord($teaching);
        $teaching->delete();

        return back()->with('success', 'Đã xóa dòng giảng dạy.');
    }

    public function import(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'teacher_id' => auth()->user()->role === 'admin' ? 'required|exists:users,id' : 'nullable',
        ]);

        $teacherId = auth()->user()->role === 'admin'
            ? (int) $validated['teacher_id']
            : auth()->id();

        $import = new TeachingRecordsImport($teacherId);
        Excel::import($import, $request->file('file'));

        if (!empty($import->missingHeaders)) {
            return back()->with('error', 'File thiếu cột bắt buộc: ' . implode(', ', $import->missingHeaders) . '.');
        }

        $message = "Đã nhập {$import->importedCount} dòng giảng dạy.";
        if ($import->updatedCount > 0) {
            $message .= " Cập nhật {$import->updatedCount} dòng đã có.";
        }
        if ($import->invalidCount > 0) {
            $message .= " Bỏ qua {$import->invalidCount} dòng thiếu dữ liệu.";
        }

        return back()->with($import->importedCount > 0 || $import->updatedCount > 0 ? 'success' : 'error', $message);
    }

    private function validatedData(Request $request): array
    {
        $teacherRule = auth()->user()->role === 'admin' ? 'required|exists:users,id' : 'nullable';

        return $request->validate([
            'teacher_id' => $teacherRule,
            'course_id' => 'nullable|exists:courses,id',
            'class_id' => 'nullable|exists:classes,id',
            'subject_name' => 'required|string|max:255',
            'class_name' => 'nullable|string|max:255',
            'center_name' => 'nullable|string|max:255',
            'term_code' => 'nullable|string|max:50',
            'planned_sessions' => 'nullable|integer|min:0|max:999',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:' . implode(',', array_keys(TeachingRecord::statuses())),
            'note' => 'nullable|string|max:2000',
        ]);
    }

    private function fillLinkedData(array $data): array
    {
        $data['planned_sessions'] = (int) ($data['planned_sessions'] ?? 0);
        $teacherId = (int) $data['teacher_id'];

        if (empty($data['course_id'])) {
            $data['course_id'] = $this->matchCourseId($data['subject_name'], $teacherId);
        }

        if (empty($data['class_id']) && !empty($data['class_name'])) {
            $data['class_id'] = $this->matchClassId($data['class_name'], $teacherId);
        }

        if (!empty($data['course_id'])) {
            $course = Course::find($data['course_id']);
            if ($course && blank($data['subject_name'])) {
                $data['subject_name'] = $course->title;
            }
        }

        if (!empty($data['class_id'])) {
            $classroom = Classroom::find($data['class_id']);
            if ($classroom && blank($data['class_name'])) {
                $data['class_name'] = $classroom->name;
            }
        }

        return $data;
    }

    private function matchCourseId(string $subjectName, int $teacherId): ?int
    {
        $keyword = trim($subjectName);
        if ($keyword === '') {
            return null;
        }

        return Course::query()
            ->where('course_type', 'delivery')
            ->notArchived()
            ->when(auth()->user()->role !== 'admin', fn ($q) => $q->where('teacher_id', $teacherId))
            ->where(function ($q) use ($keyword) {
                $q->where('title', $keyword)
                    ->orWhere('title', 'like', "%{$keyword}%")
                    ->orWhereRaw('? LIKE CONCAT("%", title, "%")', [$keyword]);
            })
            ->orderByRaw('CASE WHEN title = ? THEN 0 ELSE 1 END', [$keyword])
            ->value('id');
    }

    private function matchClassId(string $className, int $teacherId): ?int
    {
        $keyword = trim($className);
        if ($keyword === '') {
            return null;
        }

        return Classroom::query()
            ->notArchived()
            ->when(auth()->user()->role !== 'admin', fn ($q) => $q->where('teacher_id', $teacherId))
            ->where(function ($q) use ($keyword) {
                $q->where('name', $keyword)
                    ->orWhere('code', $keyword)
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhereRaw('? LIKE CONCAT("%", code, "%")', [$keyword]);
            })
            ->orderByRaw('CASE WHEN code = ? OR name = ? THEN 0 ELSE 1 END', [$keyword, $keyword])
            ->value('id');
    }

    private function authorizeAccess(): void
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'teacher'], true), 403);
    }

    private function authorizeRecord(TeachingRecord $record): void
    {
        $this->authorizeAccess();
        abort_if(auth()->user()->role === 'teacher' && $record->teacher_id !== auth()->id(), 403);
    }
}
