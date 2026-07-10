<?php

namespace App\Providers;

use App\Models\Classroom;
use App\Models\Course;
use App\Policies\ClassroomPolicy;
use App\Policies\CoursePolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL; // ✅ THÊM DÒNG NÀY
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use App\Models\SmartNotification;

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

        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            if (!auth()->check()) {
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
