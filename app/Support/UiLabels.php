<?php

namespace App\Support;

final class UiLabels
{
    /** @var array<string, string> */
    private const ROLES = [
        'admin' => 'Quản trị viên',
        'teacher' => 'Giáo viên',
        'student' => 'Học viên',
    ];

    /** @var array<string, string> */
    private const STATUSES = [
        'active' => 'Đang hoạt động',
        'inactive' => 'Đã vô hiệu hóa',
        'expired' => 'Đã hết hạn',
        'draft' => 'Bản nháp',
        'published' => 'Đang sử dụng',
        'hidden' => 'Đang ẩn',
        'archived' => 'Đã lưu trữ',
        'pending' => 'Đang chờ',
        'processing' => 'Đang xử lý',
        'success' => 'Thành công',
        'completed' => 'Hoàn tất',
        'failed' => 'Thất bại',
        'cancelled' => 'Đã hủy',
        'paid' => 'Đã thanh toán',
        'unpaid' => 'Chưa thanh toán',
        'overdue' => 'Quá hạn',
    ];

    public static function role(?string $role): string
    {
        return self::ROLES[$role ?? ''] ?? 'Người dùng';
    }

    public static function status(?string $status): string
    {
        return self::STATUSES[$status ?? ''] ?? ucfirst((string) $status);
    }

    public static function statusTone(?string $status): string
    {
        return match ($status) {
            'active', 'published', 'success', 'completed', 'paid' => 'success',
            'draft', 'pending', 'processing', 'unpaid' => 'warning',
            'inactive', 'expired', 'failed', 'cancelled', 'overdue' => 'danger',
            'hidden', 'archived' => 'muted',
            default => 'info',
        };
    }

    public static function roleTone(?string $role): string
    {
        return match ($role) {
            'admin' => 'danger',
            'teacher' => 'primary',
            'student' => 'success',
            default => 'muted',
        };
    }
}
