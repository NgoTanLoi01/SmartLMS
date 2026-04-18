<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClassManagementController;
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
});
