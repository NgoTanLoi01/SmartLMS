<?php

namespace App\Http\Controllers;

use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    // Hiển thị danh sách
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $assignments = Assignments::with('course')->latest()->get();
        } elseif ($user->role === 'teacher') {
            $courseIds = Course::where('teacher_id', $user->id)->pluck('id');
            $assignments = Assignments::with('course')->whereIn('course_id', $courseIds)->latest()->get();
        } else {
            // Học sinh: Chỉ lấy bài tập trạng thái 'published' và thuộc lớp đang học
            $classIds = $user->classes()->pluck('classes.id');
            $courseIds = Course::visibleToStudents()
                ->whereHas('classes', function ($q) use ($classIds) {
                    $q->whereIn('classes.id', $classIds);
                })
                ->pluck('id');

            $assignments = Assignments::with([
                'course',
                'submissions' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                },
            ])
                ->whereIn('course_id', $courseIds)
                ->visibleToStudents()
                ->latest()
                ->get();
        }

        $courses = Course::all();

        return view('assignments.index', compact('assignments', 'courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lesson_id' => 'required|exists:lessons,id',
            'title' => 'required|string|max:255',
            'instructions' => 'required|string',
            'due_date' => 'required|date',
            'allowed_extensions' => 'nullable|string',
            'max_file_size' => 'nullable|integer',
            'status' => 'required|in:draft,published,hidden',
            'available_from' => 'nullable|date',
        ]);

        $data = $request->all();
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        Assignments::create($data);
        return back()->with('success', 'Đã tạo bài tập thành công!');
    }

    // Giáo viên chấm điểm
    public function grade(Request $request, $submissionId)
    {
        $request->validate([
            'grade' => 'required|numeric|min:0|max:10',
            'feedback' => 'nullable|string',
        ]);

        $submission = AssignmentSubmission::findOrFail($submissionId);
        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
        ]);

        return back()->with('success', 'Đã lưu điểm và nhận xét!');
    }
    // 1. Hàm lấy danh sách bài nộp cho Giáo viên (Dùng AJAX để load vào Modal)
    public function listSubmissions($id)
    {
        $assignment = Assignments::with('course.classes.students')->findOrFail($id);

        // Lấy danh sách ID học sinh thuộc các lớp có gán khóa học này
        $students = $assignment->course->classes->flatMap->students->unique('id');

        // Lấy danh sách các bài đã nộp cho assignment này
        $submissions = AssignmentSubmission::where('assignment_id', $id)->get()->keyBy('user_id');

        // Kết hợp dữ liệu: Học sinh + Bài nộp (nếu có)
        $data = $students->map(function ($student) use ($submissions) {
            $submission = $submissions->get($student->id);
            return [
                'student_name' => $student->name,
                'student_email' => $student->email,
                'submitted_at' => $submission ? $submission->submitted_at->format('d/m/Y H:i') : null,
                'file_url' => $submission ? asset('storage/' . $submission->file_path) : null,
                'grade' => $submission ? $submission->grade : null,
                'submission_id' => $submission ? $submission->id : null,
                'feedback' => $submission ? $submission->feedback : null,
            ];
        });

        return response()->json([
            'assignment_title' => $assignment->title,
            'submissions' => $data,
        ]);
    }

    public function submit(Request $request, $id)
    {
        $assignment = Assignments::with('course.classes')->findOrFail($id);
        $user = auth()->user();

        $studentClassIds = $user->classes()->pluck('classes.id');
        $hasAccess = $assignment->course->classes
            ->pluck('id')
            ->intersect($studentClassIds)
            ->isNotEmpty();

        if (!$hasAccess || !$assignment->course->isVisibleToStudents() || !$assignment->isVisibleToStudents()) {
            return back()->withErrors(['Bài tập này chưa được mở cho học sinh.']);
        }

        // 1. Lấy thông tin bài nộp cũ nếu có
        $oldSubmission = AssignmentSubmission::where('assignment_id', $id)->where('user_id', $user->id)->first();

        // 2. Validate file mới
        $allowed = $assignment->allowed_extensions ?? 'pdf,docx,zip,png,jpg,jpeg';
        $maxSize = $assignment->max_file_size ?? 10240;

        $request->validate([
            'file' => 'required|file|mimes:' . str_replace(' ', '', $allowed) . "|max:{$maxSize}",
        ]);

        // 3. Nếu đã có bài nộp cũ, thực hiện xóa file cũ trước khi lưu file mới
        if ($oldSubmission && $oldSubmission->file_path) {
            if (Storage::disk('public')->exists($oldSubmission->file_path)) {
                Storage::disk('public')->delete($oldSubmission->file_path);
            }
        }

        // 4. Lưu file mới vào folder assignments
        $filePath = $request->file('file')->store('assignments', 'public');

        // 5. Cập nhật hoặc tạo mới record trong Database
        AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $id, 'user_id' => $user->id],
            [
                'file_path' => $filePath,
                'submitted_at' => now(),
            ],
        );

        return back()->with('success', 'Bạn đã cập nhật bài nộp thành công!');
    }

    // Học sinh hủy bài đã nộp
    public function deleteSubmission($id)
    {
        $submission = AssignmentSubmission::where('id', $id)
            ->where('user_id', auth()->id()) // Chỉ cho phép xóa bài của chính mình
            ->firstOrFail();

        // Không cho phép xóa nếu đã có điểm
        if ($submission->grade !== null) {
            return back()->withErrors(['Không thể hủy bài nộp vì giáo viên đã chấm điểm!']);
        }

        // Xóa file vật lý trong storage
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($submission->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($submission->file_path);
        }

        // Xóa record trong DB
        $submission->delete();

        return back()->with('success', 'Đã hủy bài nộp thành công!');
    }
    // Hàm xử lý cập nhật bài tập (Sửa)
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'required|string',
            'due_date' => 'required|date',
            'lesson_id' => 'required|exists:lessons,id',
            'status' => 'nullable|in:draft,published,hidden',
            'available_from' => 'nullable|date',
        ]);

        // SỬA CHỖ NÀY
        $assignment = Assignments::findOrFail($id);

        $validated['status'] = $validated['status'] ?? $assignment->status;
        $validated['published_at'] = $validated['status'] === 'published' ? ($assignment->published_at ?? now()) : null;

        $assignment->update($validated);

        return back()->with('success', 'Đã cập nhật bài tập thành công!');
    }

    // Hàm xử lý xóa bài tập (Xóa)
    public function destroy($id)
    {
        $assignment = \App\Models\Assignments::findOrFail($id);

        $assignment->delete();

        return back()->with('success', 'Đã xóa bài tập thành công!');
    }
}
