<?php

return [
    'timezone' => env('BACKUP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'disk_upload' => env('BACKUP_UPLOAD_DISK'),
    'remote_directory' => trim(env('BACKUP_REMOTE_DIRECTORY', 'backups'), '/'),
    'local_directory' => storage_path('app/backups'),
    'keep_local_copies' => (int) env('BACKUP_KEEP_LOCAL_COPIES', 10),
    'schedule' => [
        'enabled' => env('BACKUP_SCHEDULE_ENABLED', false),
        'time' => env('BACKUP_SCHEDULE_TIME', '02:00'),
        'upload_to_r2' => env('BACKUP_SCHEDULE_UPLOAD_R2', false),
    ],
];
