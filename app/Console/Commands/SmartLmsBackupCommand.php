<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class SmartLmsBackupCommand extends Command
{
    protected $signature = 'smartlms:backup {--upload-r2 : Upload bản backup lên Cloudflare R2 sau khi tạo file local}';

    protected $description = 'Tạo backup database SmartLMS dạng .sql.gz';

    public function handle(BackupService $backupService): int
    {
        $this->info('Đang tạo backup database SmartLMS...');

        $backup = $backupService->runDatabaseBackup([
            'triggered_by' => 'command',
            'upload_r2' => (bool) $this->option('upload-r2'),
        ]);

        if ($backup->isSuccessful()) {
            $this->info('Backup thành công.');
            $this->line('File: ' . $backup->filename);
            $this->line('Dung lượng: ' . $backup->formattedSize());
            $this->line('Local: ' . $backup->local_path);

            if ($backup->remote_path) {
                $this->line('R2: ' . $backup->remote_disk . '://' . $backup->remote_path);
            }

            return self::SUCCESS;
        }

        $this->error('Backup thất bại: ' . $backup->error_message);

        return self::FAILURE;
    }
}
