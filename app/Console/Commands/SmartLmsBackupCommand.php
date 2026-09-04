<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SmartLmsBackupCommand extends Command
{
    protected $signature = 'smartlms:backup {--upload-r2 : Upload bản backup lên Cloudflare R2 sau khi tạo file local}';

    protected $description = 'Tạo gói backup toàn hệ thống SmartLMS gồm database và file quan trọng';

    public function handle(BackupService $backupService): int
    {
        $lock = Cache::lock(BackupService::LOCK_NAME, max(60, (int) config('backup.restore_lock_seconds', 3600)));
        if (! $lock->get()) {
            $this->warn('Một tiến trình backup/khôi phục khác đang chạy.');

            return self::FAILURE;
        }

        $this->info('Đang tạo gói backup toàn hệ thống SmartLMS...');

        try {
            $backup = $backupService->runFullBackup([
                'triggered_by' => 'command',
                'upload_r2' => (bool) $this->option('upload-r2'),
            ]);
        } finally {
            $lock->release();
        }

        if ($backup->isSuccessful()) {
            $this->info('Backup thành công.');
            $this->line('File: '.$backup->filename);
            $this->line('Dung lượng: '.$backup->formattedSize());
            $this->line('Local: '.$backup->local_path);

            if ($backup->remote_path) {
                $this->line('R2: '.$backup->remote_disk.'://'.$backup->remote_path);
            }

            return self::SUCCESS;
        }

        $this->error('Backup thất bại: '.$backup->error_message);

        return self::FAILURE;
    }
}
