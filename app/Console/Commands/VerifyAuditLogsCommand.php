<?php

namespace App\Console\Commands;

use App\Services\AuditFailureReporter;
use App\Services\AuditIntegrityService;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

class VerifyAuditLogsCommand extends Command
{
    protected $signature = 'smartlms:audit-verify';

    protected $description = 'Kiểm tra chữ ký và toàn bộ chuỗi hash của audit log';

    public function handle(AuditIntegrityService $integrity, AuditFailureReporter $reporter): int
    {
        $result = $integrity->verify();

        if (! $result['valid']) {
            $reporter->report('audit_integrity_verification', $result['message']);
            $this->error($result['message']);

            return self::FAILURE;
        }

        AuditLogger::log(
            AuditLogger::AUDIT_INTEGRITY_VERIFIED,
            null,
            null,
            null,
            ['checked_count' => $result['checked'], 'last_hash' => $result['last_hash']],
            'Hệ thống tự động kiểm tra chuỗi audit log thành công.'
        );
        $this->info($result['message']);

        return self::SUCCESS;
    }
}
