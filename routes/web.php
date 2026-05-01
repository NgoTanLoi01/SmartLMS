<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClassManagementController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizAttemptController;

/*
|--------------------------------------------------------------------------
| XÁC THỰC & ĐĂNG NHẬP (AUTHENTICATION)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| CÁC ROUTE YÊU CẦU ĐĂNG NHẬP (AUTH MIDDLEWARE)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // ==========================================
    // 1. DASHBOARD
    // ==========================================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ==========================================
    // 2. QUẢN LÝ NGƯỜI DÙNG TỔNG (ADMIN)
    // ==========================================
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');

    // Cập nhật thông tin cá nhân (Profile)
    Route::put('/profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // ==========================================
    // 3. QUẢN LÝ LỚP HỌC (CLASSROOMS)
    // ==========================================
    Route::get('/classes', [ClassManagementController::class, 'index'])->name('classes.index');
    Route::post('/classes', [ClassManagementController::class, 'store'])->name('classes.store');
    Route::put('/classes/{id}', [ClassManagementController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{id}', [ClassManagementController::class, 'destroy'])->name('classes.destroy');

    // Quản lý Học sinh trong Lớp học
    Route::get('/classes/{classId}/students', [ClassManagementController::class, 'getStudentsByClass'])->name('classes.students.index');
    Route::post('/classes/{classId}/students', [ClassManagementController::class, 'storeStudent'])->name('classes.students.store');
    Route::delete('/classes/{classId}/students/{studentId}', [ClassManagementController::class, 'removeStudent'])->name('classes.students.destroy');

    // Import Học sinh từ Excel
    Route::post('/classes/{classId}/students/import', [ClassManagementController::class, 'importStudents'])->name('classes.students.import');

    // ==========================================
    // 4. QUẢN LÝ KHÓA HỌC (COURSES)
    // ==========================================
    // Route resource tự động tạo: index, create, store, show, edit, update, destroy
    Route::resource('courses', CourseController::class);

    // ==========================================
    // 5. QUẢN LÝ CHƯƠNG MỤC (MODULES)
    // ==========================================
    Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
    Route::put('/modules/{id}', [ModuleController::class, 'update'])->name('modules.update');
    Route::delete('/modules/{id}', [ModuleController::class, 'destroy'])->name('modules.destroy');

    // ==========================================
    // 6. QUẢN LÝ BÀI GIẢNG (LESSONS)
    // ==========================================
    Route::post('/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::put('/lessons/{id}', [LessonController::class, 'update'])->name('lessons.update');
    Route::delete('/lessons/{id}', [LessonController::class, 'destroy'])->name('lessons.destroy');
    Route::post('/lessons/{id}/complete', [LessonController::class, 'toggleComplete'])->name('lessons.complete');

    // ==========================================
    // 7. QUẢN LÝ BÀI TẬP & NỘP BÀI (ASSIGNMENTS)
    // ==========================================
    // Quản lý đề bài (Giáo viên)
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::put('/assignments/{id}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    // Nộp bài & Chấm điểm (Học sinh & Giáo viên)
    Route::post('/assignments/{id}/submit', [AssignmentController::class, 'submit'])->name('assignments.submit');
    Route::get('/assignments/{id}/submissions-list', [AssignmentController::class, 'listSubmissions'])->name('assignments.submissions.list');
    Route::post('/submissions/{id}/grade', [AssignmentController::class, 'grade'])->name('assignments.grade');
    Route::delete('/submissions/{id}/delete', [AssignmentController::class, 'deleteSubmission'])->name('assignments.submissions.delete');

    // ==========================================
    // 8. ĐIỂM DANH HỌC SINH (ATTENDANCE)
    // ==========================================
    Route::get('/courses/{id}/attendance', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/courses/{id}/attendance/save', [AttendanceController::class, 'save'])->name('attendance.save');
    Route::post('/courses/{id}/attendance/add-column', [AttendanceController::class, 'addColumn'])->name('attendance.addColumn');
    Route::delete('/attendance/column/{id}', [AttendanceController::class, 'deleteColumn'])->name('attendance.deleteColumn');
    Route::post('/attendance/column/{id}/update', [AttendanceController::class, 'updateColumn'])->name('attendance.updateColumn');
    Route::get('/courses/{id}/attendance/export', [AttendanceController::class, 'exportExcel'])->name('attendance.export');

    // ==========================================
    // 9. QUẢN LÝ BÀI KIỂM TRA (QUIZZES)
    // ==========================================
    // Quản lý đề thi & xem bảng điểm (Giáo viên)
    Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/quizzes/{id}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::delete('/quizzes/{id}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
    Route::get('/quizzes/{id}/submissions', [QuizController::class, 'submissions'])->name('quizzes.submissions');

    // Quản lý câu hỏi trong đề thi (Giáo viên)
    Route::post('/quizzes/{id}/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::put('/questions/{id}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{id}', [QuestionController::class, 'destroy'])->name('questions.destroy');

    // Làm bài & Xem lại bài (Học sinh)
    Route::get('/quizzes/{id}/attempt', [QuizAttemptController::class, 'create'])->name('quizzes.attempt');
    Route::post('/quizzes/{id}/attempt', [QuizAttemptController::class, 'store'])->name('quizzes.submit');
    Route::get('/attempts/{id}/review', [QuizAttemptController::class, 'review'])->name('quizzes.review');
    // ==========================================
    // 10. QUẢN LÝ THỜI KHÓA BIỂU (SCHEDULES)
    // ==========================================
    Route::get('/schedules', [App\Http\Controllers\ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [App\Http\Controllers\ScheduleController::class, 'store'])->name('schedules.store');
    Route::put('/schedules/{id}', [App\Http\Controllers\ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/schedules/{id}', [App\Http\Controllers\ScheduleController::class, 'destroy'])->name('schedules.destroy');
    Route::get('/schedules', [App\Http\Controllers\ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/get-courses/{class_id}', [App\Http\Controllers\ScheduleController::class, 'getCoursesByClass']);
    Route::post('/schedules', [App\Http\Controllers\ScheduleController::class, 'store'])->name('schedules.store');
    // ==========================================
    // 11. NGÂN HÀNG CÂU HỎI (QUESTION BANK)
    // ==========================================
    Route::get('/question-bank', [QuestionController::class, 'index'])->name('questions.index');
    Route::post('/question-bank', [QuestionController::class, 'storeBank'])->name('questions.storeBank');
    Route::post('/question-bank/import', [QuestionController::class, 'importBank'])->name('questions.importBank');
    Route::put('/question-bank/{id}', [QuestionController::class, 'updateBank'])->name('questions.updateBank');
    Route::delete('/question-bank/{id}', [QuestionController::class, 'destroyBank'])->name('questions.destroyBank');
});
