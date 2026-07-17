<?php

namespace App\Providers;

use App\Models\Assignments;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceColumn;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\DocumentChunk;
use App\Models\LearningProgram;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Schedule;
use App\Models\SharedDocument;
use App\Models\SmartNotification;
use App\Models\TeachingContract;
use App\Models\TeachingRecord;
use App\Policies\AssignmentPolicy;
use App\Policies\AssignmentSubmissionPolicy;
use App\Policies\AttendanceColumnPolicy;
use App\Policies\ClassroomPolicy;
use App\Policies\CoursePolicy;
use App\Policies\DocumentChunkPolicy;
use App\Policies\LearningProgramPolicy;
use App\Policies\LessonPolicy;
use App\Policies\ModulePolicy;
use App\Policies\QuestionBankPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\QuizAttemptPolicy;
use App\Policies\QuizPolicy;
use App\Policies\SchedulePolicy;
use App\Policies\SharedDocumentPolicy;
use App\Policies\TeachingContractPolicy;
use App\Policies\TeachingRecordPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate; // ✅ THÊM DÒNG NÀY
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Classroom::class, ClassroomPolicy::class);
        Gate::policy(Module::class, ModulePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(Assignments::class, AssignmentPolicy::class);
        Gate::policy(AssignmentSubmission::class, AssignmentSubmissionPolicy::class);
        Gate::policy(Quiz::class, QuizPolicy::class);
        Gate::policy(QuizAttempt::class, QuizAttemptPolicy::class);
        Gate::policy(DocumentChunk::class, DocumentChunkPolicy::class);
        Gate::policy(AttendanceColumn::class, AttendanceColumnPolicy::class);
        Gate::policy(Schedule::class, SchedulePolicy::class);
        Gate::policy(SharedDocument::class, SharedDocumentPolicy::class);
        Gate::policy(QuestionBank::class, QuestionBankPolicy::class);
        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(LearningProgram::class, LearningProgramPolicy::class);
        Gate::policy(TeachingRecord::class, TeachingRecordPolicy::class);
        Gate::policy(TeachingContract::class, TeachingContractPolicy::class);

        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            if (! auth()->check()) {
                return;
            }

            $notifications = SmartNotification::forUser(auth()->id())->latest();
            $view->with([
                'topbarNotifications' => (clone $notifications)->limit(6)->get(),
                'topbarUnreadCount' => (clone $notifications)->unread()->count(),
            ]);
        });

        if (config('app.env') !== 'local' || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }
    }
}
