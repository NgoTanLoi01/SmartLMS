<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AuditFailureReporter
{
    public function report(string $operation, Throwable|string $failure): void
    {
        $message = $failure instanceof Throwable ? $failure->getMessage() : $failure;
        $context = [
            'operation' => $operation,
            'error' => $message,
            'exception' => $failure instanceof Throwable ? $failure::class : null,
        ];

        try {
            Log::channel((string) config('audit.alert_channel', 'stack'))
                ->critical('Cảnh báo toàn vẹn audit log SmartLMS', $context);
        } catch (Throwable) {
            Log::critical('Cảnh báo toàn vẹn audit log SmartLMS', $context);
        }

        $this->notifyAdministrators($operation);
    }

    private function notifyAdministrators(string $operation): void
    {
        try {
            if (! Schema::hasTable('users') || ! Schema::hasTable('smart_notifications')) {
                return;
            }

            $cooldown = max(60, (int) config('audit.alert_cooldown_seconds', 300));
            $cacheKey = 'audit-alert:'.sha1($operation);
            if (! Cache::add($cacheKey, true, $cooldown)) {
                return;
            }

            User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where(fn ($query) => $query->whereNull('is_active')->orWhere('is_active', true))
                ->pluck('id')
                ->each(fn ($adminId) => app(NotificationCenter::class)->notifyUser(
                    (int) $adminId,
                    'system',
                    'Cảnh báo audit log',
                    "Không thể bảo đảm audit log cho thao tác {$operation}. Vui lòng kiểm tra nhật ký vận hành.",
                    route('audit-logs.index'),
                    ['operation' => $operation],
                    'audit-alert:'.sha1($operation.':'.now()->format('Y-m-d-H-i')),
                ));
        } catch (Throwable $exception) {
            Log::error('Không thể gửi cảnh báo audit log đến quản trị viên.', [
                'operation' => $operation,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
