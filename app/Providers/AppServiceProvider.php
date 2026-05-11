<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL; // ✅ THÊM DÒNG NÀY

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();


        if (config('app.env') !== 'local' || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }
    }
}
