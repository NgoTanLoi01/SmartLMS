<?php

namespace App\Console\Commands;

use App\Services\AuditIntegrityService;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

class ArchiveAuditLogsCommand extends Command
{
    protected $signature = 'smartlms:audit-archive {--days= : Số ngày giữ bản ghi ở danh sách đang hoạt động} {--dry-run : Chỉ đếm, không lưu trữ}';

    protected $description = 'Lưu trữ audit log quá hạn theo chính sách retention mà không xóa bản ghi';

    public function handle(AuditIntegrityService $integrity): int
    {
        $days = max(1, (int) ($this->option('days') ?: config('audit.retention_days', 365)));
        $dryRun = (bool) $this->option('dry-run');
        $count = $integrity->archiveExpired($days, $dryRun);

        if ($dryRun) {
            $this->info("Có {$count} audit log sẽ được lưu trữ theo chính sách {$days} ngày.");

            return self::SUCCESS;
        }

        AuditLogger::log(
            AuditLogger::AUDIT_LOGS_ARCHIVED,
            null,
            null,
            null,
            ['archived_count' => $count, 'retention_days' => $days],
            'Hệ thống áp dụng chính sách lưu trữ audit log.'
        );
        $this->info("Đã lưu trữ {$count} audit log; không có bản ghi nào bị xóa.");

        return self::SUCCESS;
    }
}
