<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ClassManagementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;

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
    Route::put('/profile/update-password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/users/{id}/reset-password', [App\Http\Controllers\UserController::class, 'resetPassword'])->name('users.resetPassword');

    // ==========================================
    // 3. QUẢN LÝ LỚP HỌC (CLASSROOMS)
    // ==========================================
    Route::get('/classes', [ClassManagementController::class, 'index'])->name('classes.index');
    Route::post('/classes', [ClassManagementController::class, 'store'])->name('classes.store');
    Route::put('/classes/{id}', [ClassManagementController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{id}', [ClassManagementController::class, 'destroy'])->name('classes.destroy');

    // 3.1. Quản lý Học sinh trong Lớp học
    Route::get('/classes/{classId}/students', [ClassManagementController::class, 'getStudentsByClass'])->name('classes.students.index');
    Route::post('/classes/{classId}/students', [ClassManagementController::class, 'storeStudent'])->name('classes.students.store');
    Route::delete('/classes/{classId}/students/{studentId}', [ClassManagementController::class, 'removeStudent'])->name('classes.students.destroy');

    // 3.2. Import Học sinh từ Excel
    Route::post('/classes/{classId}/students/import', [ClassManagementController::class, 'importStudents'])->name('classes.students.import');

    // ==========================================
    // 4. QUẢN LÝ KHÓA HỌC (COURSES)
    // ==========================================
    // Route resource sẽ tự động tạo đủ 7 hàm: index, create, store, show, edit, update, destroy
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
    // 7. QUẢN LÝ BÀI TẬP NỘP (ASSIGNMENTS)
    // ==========================================
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::post('/assignments/{id}/submit', [AssignmentController::class, 'submit'])->name('assignments.submit');
    Route::post('/submissions/{id}/grade', [AssignmentController::class, 'grade'])->name('assignments.grade');
    Route::put('/assignments/{id}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::get('/assignments/{id}/submissions-list', [AssignmentController::class, 'listSubmissions'])->name('assignments.submissions.list');
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
    // ==========================================
    Route::post('/quizzes', [App\Http\Controllers\QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/quizzes/{id}', [App\Http\Controllers\QuizController::class, 'show'])->name('quizzes.show');
    Route::post('/quizzes/{id}/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::delete('/quizzes/{id}', [App\Http\Controllers\QuizController::class, 'destroy'])->name('quizzes.destroy');
    Route::delete('/questions/{id}', [App\Http\Controllers\QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::get('/quizzes/{id}/attempt', [App\Http\Controllers\QuizAttemptController::class, 'create'])->name('quizzes.attempt');
    Route::post('/quizzes/{id}/attempt', [App\Http\Controllers\QuizAttemptController::class, 'store'])->name('quizzes.submit');
    // Cập nhật câu hỏi và 4 đáp án
    Route::put('/questions/{id}', [App\Http\Controllers\QuestionController::class, 'update'])->name('questions.update');
    Route::get('/attempts/{id}/review', [App\Http\Controllers\QuizAttemptController::class, 'review'])->name('quizzes.review');
    Route::get('/quizzes/{id}/submissions', [App\Http\Controllers\QuizController::class, 'submissions'])->name('quizzes.submissions');
});
