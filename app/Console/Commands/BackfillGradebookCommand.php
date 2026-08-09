<?php

namespace App\Console\Commands;

use App\Domain\Gradebook\GradebookException;
use App\Services\GradebookMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackfillGradebookCommand extends Command
{
    protected $signature = 'smartlms:gradebook-backfill {--manifest= : Manifest JSON đã được duyệt} {--dry-run : Chỉ preflight, tuyệt đối không ghi DB}';

    protected $description = 'Shadow backfill Gradebook từ manifest explicit và idempotent';

    public function handle(GradebookMigrationService $service): int
    {
        try {
            $manifest = $this->manifest();
            $result = $service->backfill($manifest, (bool) $this->option('dry-run'));
        } catch (GradebookException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($result['errors'] !== []) {
            $this->error('Preflight có lỗi; không có dữ liệu nào được ghi.');

            return self::FAILURE;
        }

        $this->info($result['dry_run'] ? 'Dry-run hoàn tất; không ghi dữ liệu.' : 'Shadow backfill hoàn tất; nguồn đọc legacy không thay đổi.');

        return self::SUCCESS;
    }

    /** @return array<string,mixed> */
    private function manifest(): array
    {
        $path = (string) $this->option('manifest');
        if ($path === '' || ! File::isFile($path)) {
            throw new GradebookException('--manifest phải trỏ tới file JSON tồn tại.');
        }
        $manifest = json_decode(File::get($path), true);
        if (! is_array($manifest)) {
            throw new GradebookException('Manifest JSON không hợp lệ.');
        }

        return $manifest;
    }
}
