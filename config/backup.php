<?php

return [
    'timezone' => env('BACKUP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'disk_upload' => env('BACKUP_UPLOAD_DISK'),
    'remote_directory' => trim(env('BACKUP_REMOTE_DIRECTORY', 'backups'), '/'),
    'local_directory' => storage_path('app/backups'),
    'keep_local_copies' => (int) env('BACKUP_KEEP_LOCAL_COPIES', 10),
    'file_disks' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('BACKUP_FILE_DISKS', 'local,public'))
    ))),
    'restore_lock_seconds' => (int) env('BACKUP_RESTORE_LOCK_SECONDS', 3600),
    'include_vector_database' => env('BACKUP_INCLUDE_VECTOR_DATABASE', true),
    'vector_connection' => env('BACKUP_VECTOR_CONNECTION', 'pgsql'),
    'schedule' => [
        'enabled' => env('BACKUP_SCHEDULE_ENABLED', false),
        'time' => env('BACKUP_SCHEDULE_TIME', '02:00'),
        'upload_to_r2' => env('BACKUP_SCHEDULE_UPLOAD_R2', false),
    ],
];
