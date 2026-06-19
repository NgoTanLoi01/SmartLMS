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


        if (config('app.env') !== 'local' || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }
    }
}
