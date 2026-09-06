<?php

return [
    /*
    | Khóa này phải được quản lý bên ngoài database. Khi để trống, APP_KEY
    | được dùng làm khóa HMAC để các thay đổi trực tiếp trong database không
    | thể tự tạo lại chữ ký hợp lệ nếu không có khóa ứng dụng.
    */
    'signing_key' => env('AUDIT_SIGNING_KEY') ?: env('APP_KEY'),

    'alert_channel' => env('AUDIT_ALERT_CHANNEL', 'stack'),
    'alert_cooldown_seconds' => (int) env('AUDIT_ALERT_COOLDOWN_SECONDS', 300),

    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
    'archive' => [
        'enabled' => env('AUDIT_ARCHIVE_ENABLED', true),
        'time' => env('AUDIT_ARCHIVE_TIME', '03:30'),
    ],

    'verification' => [
        'enabled' => env('AUDIT_VERIFY_ENABLED', true),
        'time' => env('AUDIT_VERIFY_TIME', '04:00'),
    ],
];
