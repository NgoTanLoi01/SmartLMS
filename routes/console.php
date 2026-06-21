<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('backup.schedule.enabled')) {
    Schedule::command('smartlms:backup' . (config('backup.schedule.upload_to_r2') ? ' --upload-r2' : ''))
        ->dailyAt(config('backup.schedule.time', '02:00'))
        ->timezone(config('backup.timezone', 'Asia/Ho_Chi_Minh'));
}
