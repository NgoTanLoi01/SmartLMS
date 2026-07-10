<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, DashboardController, UserController, ProfileController, ClassManagementController, CourseController, LearningProgramController, ModuleController, LessonController, AssignmentController, AttendanceController, QuizController, QuestionController, QuizAttemptController, ChatbotController, DocumentController, ScheduleController, StorageHealthController, StudentGradesController, StudentScheduleController, TeachingRecordController, TeachingContractController, OperationalDashboardController, OperationalReportController, AuditLogController, SystemBackupController, AiTeachingContentController, CourseQualityController, CourseMaterialController, NotificationController};
use App\Http\Controllers\ChessController;

/*
|--------------------------------------------------------------------------
| 1. XÁC THỰC & ĐĂNG NHẬP (AUTHENTICATION)
|--------------------------------------------------------------------------
*/
// Trang chủ → Landing page (chưa đăng nhập) hoặc Dashboard (đã đăng nhập)
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('landing');
})->name('home');

// Đăng nhập
Route::get('/login', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
/*
|--------------------------------------------------------------------------
| 2. CÁC ROUTE YÊU CẦU ĐĂNG NHẬP (AUTH MIDDLEWARE)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // ==========================================
    // 2.1. DASHBOARD & PROFILE
    // ==========================================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::put('/profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::middleware('role:admin')->group(function () {
        Route::get('/system/storage', [StorageHealthController::class, 'index'])->name('system.storage.index');
        Route::post('/system/storage/test', [StorageHealthController::class, 'test'])->name('system.storage.test');
        Route::get('/system/backups', [SystemBackupController::class, 'index'])->name('system.backups.index');
        Route::post('/system/backups', [SystemBackupController::class, 'store'])->name('system.backups.store');
        Route::get('/system/backups/{backup}/download', [SystemBackupController::class, 'download'])->name('system.backups.download');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::delete('/audit-logs', [AuditLogController::class, 'bulkDestroy'])->name('audit-logs.bulk-destroy');
        Route::delete('/audit-logs/{auditLog}', [AuditLogController::class, 'destroy'])->name('audit-logs.destroy');
    });
    Route::middleware('role:student')->group(function () {
        Route::get('/my-grades', [StudentGradesController::class, 'index'])->name('students.grades');
        Route::get('/my-schedule', [StudentScheduleController::class, 'index'])->name('students.schedule');
    });

    // ==========================================
    // 2.2. QUẢN LÝ NGƯỜI DÙNG TỔNG (ADMIN)
    // ==========================================
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
    });

    // ==========================================
    // 2.3. QUẢN LÝ LỚP HỌC (CLASSROOMS)
    // ==========================================
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/classes', [ClassManagementController::class, 'index'])->name('classes.index');
        Route::post('/classes', [ClassManagementController::class, 'store'])->name('classes.store');
        Route::put('/classes/{id}', [ClassManagementController::class, 'update'])->name('classes.update');
        Route::delete('/classes/{id}', [ClassManagementController::class, 'destroy'])->name('classes.destroy');

        // Quản lý Học sinh trong Lớp học
        Route::get('/classes/{classId}/progress', [ClassManagementController::class, 'showProgress'])->name('classes.progress');
        Route::post('/classes/{classId}/ai-analysis', [ClassManagementController::class, 'analyzeLearningWithAi'])->name('classes.ai-analysis');
        Route::get('/classes/{classId}/students', [ClassManagementController::class, 'getStudentsByClass'])->name('classes.students.index');
        Route::get('/classes/{classId}/students/{studentId}', [ClassManagementController::class, 'showStudent'])->name('classes.students.show');
        Route::post('/classes/{classId}/students', [ClassManagementController::class, 'storeStudent'])->name('classes.students.store');
        Route::delete('/classes/{classId}/students/{studentId}', [ClassManagementController::class, 'removeStudent'])->name('classes.students.destroy');
        Route::post('/classes/{classId}/students/import', [ClassManagementController::class, 'importStudents'])->name('classes.students.import');
    });

    // ==========================================
    // 2.4. QUẢN LÝ KHÓA HỌC (COURSES)
    // ==========================================
    Route::middleware('role:admin,teacher')->group(function () {
        Route::resource('programs', LearningProgramController::class)->except(['create', 'edit']);
        Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
        Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::patch('/courses/{course}', [CourseController::class, 'update']);
        Route::post('/courses/{course}/quality-check', [CourseQualityController::class, 'check'])->name('courses.quality-check');
        Route::post('/courses/{course}/materials', [CourseMaterialController::class, 'store'])->name('courses.materials.store');
        Route::post('/courses/{course}/materials/attach', [CourseMaterialController::class, 'attachExisting'])->name('courses.materials.attach');
        Route::put('/courses/{course}/materials/assignments/{assignment}', [CourseMaterialController::class, 'updateAssignment'])->name('courses.materials.assignments.update');
        Route::delete('/courses/{course}/materials/assignments/{assignment}', [CourseMaterialController::class, 'destroyAssignment'])->name('courses.materials.assignments.destroy');
        Route::delete('/courses/{course}/materials/{material}', [CourseMaterialController::class, 'destroyMaterial'])->name('courses.materials.destroy');
        Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    });
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/materials', [CourseMaterialController::class, 'library'])->name('materials.index');
    Route::get('/courses/{course}/materials', [CourseMaterialController::class, 'index'])->name('courses.materials.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/materials/{assignment}/download', [CourseMaterialController::class, 'download'])->name('materials.download');

    // ==========================================
    // 2.5. QUẢN LÝ CHƯƠNG MỤC & BÀI GIẢNG (CURRICULUM)
    // ==========================================
    Route::middleware('role:admin,teacher')->group(function () {
        Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
        Route::post('/modules/reorder', [ModuleController::class, 'reorder'])->name('modules.reorder');
        Route::put('/modules/{id}', [ModuleController::class, 'update'])->name('modules.update');
        Route::delete('/modules/{id}', [ModuleController::class, 'destroy'])->name('modules.destroy');

        Route::post('/lessons', [LessonController::class, 'store'])->name('lessons.store');
        Route::post('/lessons/reorder', [LessonController::class, 'reorder'])->name('lessons.reorder');
        Route::put('/lessons/{id}', [LessonController::class, 'update'])->name('lessons.update');
        Route::delete('/lessons/{id}', [LessonController::class, 'destroy'])->name('lessons.destroy');
    });
    Route::get('/lessons/{id}/attachment', [LessonController::class, 'downloadAttachment'])->name('lessons.attachment');
    Route::post('/lessons/{id}/complete', [LessonController::class, 'toggleComplete'])->middleware('role:student')->name('lessons.complete');

    // ==========================================
    // 2.6. QUẢN LÝ BÀI TẬP (ASSIGNMENTS)
    // ==========================================
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::middleware('role:admin,teacher')->group(function () {
        Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
        Route::put('/assignments/{id}', [AssignmentController::class, 'update'])->name('assignments.update');
        Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
        Route::get('/assignments/{id}/submissions-list', [AssignmentController::class, 'listSubmissions'])->name('assignments.submissions.list');
        Route::post('/assignments/{id}/submissions/download', [AssignmentController::class, 'downloadSubmissionsArchive'])->name('assignments.submissions.download');
        Route::post('/submissions/{id}/ai-analysis', [AssignmentController::class, 'analyzeSubmissionWithAi'])->name('assignments.submissions.ai-analysis');
        Route::post('/submissions/{id}/grade', [AssignmentController::class, 'grade'])->name('assignments.grade');
        Route::post('/ai/teaching-content/generate', [AiTeachingContentController::class, 'generate'])->name('ai.teaching-content.generate');
    });

    Route::post('/assignments/{id}/submit', [AssignmentController::class, 'submit'])->middleware('role:student')->name('assignments.submit');
    Route::get('/submissions/{id}/review', [AssignmentController::class, 'reviewSubmission'])->name('assignments.submissions.review');
    Route::get('/submissions/{id}/file', [AssignmentController::class, 'downloadSubmissionFile'])->name('assignments.submissions.file');
    Route::get('/submissions/{id}/preview', [AssignmentController::class, 'previewSubmissionFile'])->name('assignments.submissions.preview');
    Route::delete('/submissions/{id}/delete', [AssignmentController::class, 'deleteSubmission'])->middleware('role:student')->name('assignments.submissions.delete');

    // ==========================================
    // 2.7. ĐIỂM DANH (ATTENDANCE)
    // ==========================================
    Route::get('/courses/{id}/attendance', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::middleware('role:admin,teacher')->group(function () {
        Route::post('/courses/{id}/attendance/save', [AttendanceController::class, 'save'])->name('attendance.save');
        Route::post('/courses/{id}/attendance/add-column', [AttendanceController::class, 'addColumn'])->name('attendance.addColumn');
        Route::delete('/attendance/column/{id}', [AttendanceController::class, 'deleteColumn'])->name('attendance.deleteColumn');
        Route::post('/attendance/column/{id}/update', [AttendanceController::class, 'updateColumn'])->name('attendance.updateColumn');
        Route::get('/courses/{id}/attendance/export', [AttendanceController::class, 'exportExcel'])->name('attendance.export');
    });

    // ==========================================
    // 2.8. NGÂN HÀNG CÂU HỎI & AI GENERATE (QUAN TRỌNG)
    // ==========================================
    Route::middleware('role:admin,teacher')->group(function () {
        // Các Route AI Generate đặt LÊN TRÊN để tránh xung đột /{id}
        Route::get('/quizzes/ai-generate', [QuestionController::class, 'aiGenerateView'])->name('quizzes.ai_generate');
        Route::post('/quizzes/ai-generate/process', [QuestionController::class, 'generateQuestions'])->name('quizzes.ai_generate.process');
        Route::post('/quizzes/ai-generate/save', [QuestionController::class, 'saveGeneratedQuestions'])->name('quizzes.ai_generate.save');

        // Ngân hàng câu hỏi
        Route::get('/question-bank', [QuestionController::class, 'index'])->name('questions.index');
        Route::post('/question-bank/banks', [QuestionController::class, 'storeQuestionBank'])->name('questions.banks.store');
        Route::post('/question-bank/banks/attach', [QuestionController::class, 'attachQuestionBank'])->name('questions.banks.attach');
        Route::post('/question-bank', [QuestionController::class, 'storeBank'])->name('questions.storeBank');
        Route::post('/question-bank/import', [QuestionController::class, 'importBank'])->name('questions.importBank');
        Route::put('/question-bank/{id}', [QuestionController::class, 'updateBank'])->name('questions.updateBank');
        Route::delete('/question-bank/{id}', [QuestionController::class, 'destroyBank'])->name('questions.destroyBank');
    });

    // ==========================================
    // 2.9. QUẢN LÝ BÀI KIỂM TRA (QUIZZES)
    // ==========================================
    Route::middleware('role:admin,teacher')->group(function () {
        Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
        Route::get('/quizzes/{id}', [QuizController::class, 'show'])->name('quizzes.show');
        Route::delete('/quizzes/{id}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
        Route::get('/quizzes/{id}/submissions', [QuizController::class, 'submissions'])->name('quizzes.submissions');

        // Quản lý câu hỏi trong đề thi cụ thể
        Route::post('/quizzes/{id}/questions', [QuestionController::class, 'store'])->name('questions.store');
        Route::put('/questions/{id}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('/questions/{id}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    });

    // Làm bài & Xem lại
    Route::get('/quizzes/{id}/attempt', [QuizAttemptController::class, 'create'])->middleware('role:student')->name('quizzes.attempt');
    Route::post('/quizzes/{id}/attempt', [QuizAttemptController::class, 'store'])->middleware('role:student')->name('quizzes.submit');
    Route::get('/attempts/{id}/review', [QuizAttemptController::class, 'review'])->name('quizzes.review');

    // ==========================================
    // 2.10. THỜI KHÓA BIỂU (SCHEDULES)
    // ==========================================
    Route::middleware('role:admin,teacher')->group(function () {
        Route::post('/teaching/import', [TeachingRecordController::class, 'import'])->name('teaching.import');
        Route::resource('teaching', TeachingRecordController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('/payments/import', [TeachingContractController::class, 'import'])->name('payments.import');
        Route::resource('payments', TeachingContractController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/operations/dashboard', [OperationalDashboardController::class, 'index'])->name('operations.dashboard');
        Route::get('/reports/operations', [OperationalReportController::class, 'index'])->name('reports.operations');
        Route::get('/reports/operations/export', [OperationalReportController::class, 'exportExcel'])->name('reports.operations.export');
        Route::get('/reports/operations/print', [OperationalReportController::class, 'print'])->name('reports.operations.print');

        Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/schedules/get-courses/{class_id}', [ScheduleController::class, 'getCoursesByClass']);
        Route::post('/schedules/copy-day', [ScheduleController::class, 'copyDay'])->name('schedules.copyDay');
        Route::post('/schedules/import', [ScheduleController::class, 'importExcel'])->name('schedules.import');
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::put('/schedules/{id}', [ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    });

    // ==========================================
    // 2.11. HUẤN LUYỆN AI (RAG) & CHATBOT
    // ==========================================
    // API Chatbot
    Route::post('/chatbot/send', [ChatbotController::class, 'sendMessage'])->name('chatbot.send');

    // Quản lý tài liệu huấn luyện
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/upload', [DocumentController::class, 'index'])->name('documents.upload');
        Route::post('/documents/upload', [DocumentController::class, 'store'])->name('documents.store');
        Route::delete('/documents/{name}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    });

    // ==========================================
    // 2.12. CÔNG CỤ BỔ TRỢ (TOOLS)
    // ==========================================
    Route::prefix('tools')
        ->name('tools.')
        ->group(function () {
            Route::get('/grade-calculator', function () {
                return view('tools.grade-calculator');
            })->name('grade-calculator');
            Route::prefix('chess')
                ->name('chess.')
                ->group(function () {
                    Route::get('/', [App\Http\Controllers\ChessController::class, 'index'])->name('index');
                    Route::get('/{roomId}', [App\Http\Controllers\ChessController::class, 'play'])->name('play');
                    Route::post('/{roomId}/move', [App\Http\Controllers\ChessController::class, 'broadcastMove'])->name('move');
                    Route::post('/{roomId}/finish', [App\Http\Controllers\ChessController::class, 'finish'])->middleware('auth');
                });
            Route::prefix('caro')
                ->name('caro.')
                ->group(function () {
                    Route::get('/', [App\Http\Controllers\CaroController::class, 'index'])->name('index');
                    Route::get('/{roomId}', [App\Http\Controllers\CaroController::class, 'play'])->name('play');
                    Route::post('/{roomId}/move', [App\Http\Controllers\CaroController::class, 'broadcastMove'])->name('move');
                });
        });

    Broadcast::routes(['middleware' => ['web', 'auth']]);
});
