<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('backup.schedule.enabled')) {
    Schedule::command('smartlms:backup'.(config('backup.schedule.upload_to_r2') ? ' --upload-r2' : ''))
        ->dailyAt(config('backup.schedule.time', '02:00'))
        ->timezone(config('backup.timezone', 'Asia/Ho_Chi_Minh'));
}

if (config('audit.archive.enabled')) {
    Schedule::command('smartlms:audit-archive')
        ->dailyAt(config('audit.archive.time', '03:30'))
        ->timezone(config('app.timezone', 'Asia/Ho_Chi_Minh'))
        ->withoutOverlapping();
}

if (config('audit.verification.enabled')) {
    Schedule::command('smartlms:audit-verify')
        ->dailyAt(config('audit.verification.time', '04:00'))
        ->timezone(config('app.timezone', 'Asia/Ho_Chi_Minh'))
        ->withoutOverlapping();
}
