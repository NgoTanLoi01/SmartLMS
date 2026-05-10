<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Classroom;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentImport;

class ClassManagementController extends Controller
{
    public function storeStudent(Request $request, $classId)
    {
        $classroom = Classroom::findOrFail($classId);

        if (auth()->user()->role !== 'admin' && auth()->id() !== $classroom->teacher_id) {
            return response()->json(['message' => 'Không có quyền'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $student = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
        ]);

        $classroom->students()->attach($student->id);

        return back()->with('success', 'Đã tạo học sinh và gán vào lớp thành công!');
    }

    public function getStudentsByClass($classId)
    {
        $classroom = Classroom::with('students')->findOrFail($classId);

        return view('classes.students', compact('classroom'));
    }

    public function index()
    {
        $teachers = User::where('role', 'teacher')->get();
        $courses = Course::all(); // Lấy tất cả khóa học để Admin/Teacher chọn

        if (auth()->user()->role === 'admin') {
            $classes = Classroom::withCount('students')
                ->with(['teacher', 'courses'])
                ->get();
        } else {
            $classes = Classroom::where('teacher_id', auth()->id())
                ->withCount('students')
                ->with('courses')
                ->get();
        }

        return view('classes.index', compact('classes', 'teachers', 'courses'));
    }

    public function store(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'teacher'])) {
            return back()->with('error', 'Bạn không có quyền tạo lớp học.');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:classes,code',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ];

        // Nếu là admin thì mới bắt buộc chọn teacher_id từ request
        if (auth()->user()->role === 'admin') {
            $rules['teacher_id'] = 'required|exists:users,id';
        }

        $request->validate($rules);

        // Trích đoạn cập nhật trong hàm store()
        $classroom = Classroom::create([
            'name' => $request->name,
            'code' => $request->code,
            // Gán teacher_id từ form nếu là admin, gán ID hiện tại nếu là giáo viên
            'teacher_id' => auth()->user()->role === 'admin' ? $request->teacher_id : auth()->id(),
        ]);

        if ($request->has('course_ids')) {
            $classroom->courses()->attach($request->course_ids);
        }

        return back()->with('success', 'Đã tạo lớp học và phân bổ khóa học thành công!');
    }

    public function update(Request $request, $id)
    {
        $classroom = Classroom::findOrFail($id);

        if (auth()->user()->role !== 'admin' && auth()->id() !== $classroom->teacher_id) {
            return back()->with('error', 'Bạn không có quyền sửa lớp này.');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:classes,code,' . $id,
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ];

        if (auth()->user()->role === 'admin') {
            $rules['teacher_id'] = 'required|exists:users,id';
        }

        $request->validate($rules);

        $classroom->name = $request->name;
        $classroom->code = $request->code;
        if (auth()->user()->role === 'admin') {
            $classroom->teacher_id = $request->teacher_id;
        }
        $classroom->save();

        // sync() sẽ tự động xóa các khóa học cũ không còn được chọn và thêm khóa học mới
        if ($request->has('course_ids')) {
            $classroom->courses()->sync($request->course_ids);
        } else {
            $classroom->courses()->detach(); // Xóa sạch nếu không chọn gì
        }

        return back()->with('success', 'Đã cập nhật thông tin lớp học.');
    }

    // Xóa lớp học
    public function destroy($id)
    {
        $classroom = Classroom::findOrFail($id);

        // Phân quyền
        if (auth()->user()->role !== 'admin' && auth()->id() !== $classroom->teacher_id) {
            return back()->with('error', 'Bạn không có quyền xóa lớp này.');
        }

        $classroom->delete();

        return back()->with('success', 'Đã xóa lớp học thành công.');
    }

    // Xóa học sinh khỏi lớp (Chỉ gỡ liên kết trong bảng class_user)
    public function removeStudent($classId, $studentId)
    {
        $classroom = Classroom::findOrFail($classId);

        // Phân quyền
        if (auth()->user()->role !== 'admin' && auth()->id() !== $classroom->teacher_id) {
            return back()->with('error', 'Bạn không có quyền thực hiện thao tác này.');
        }

        // detach() sẽ gỡ kết nối học sinh khỏi lớp mà không xóa tài khoản
        $classroom->students()->detach($studentId);

        return back()->with('success', 'Đã xóa học sinh khỏi lớp.');
    }

    public function importStudents(Request $request, $classId)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120', // Tối đa 5MB
        ]);

        try {
            // Gọi thư viện Excel để đọc file và chạy file StudentImport
            Excel::import(new StudentImport($classId), $request->file('file'));

            return back()->with('success', 'Đã nhập danh sách học viên từ file Excel thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
