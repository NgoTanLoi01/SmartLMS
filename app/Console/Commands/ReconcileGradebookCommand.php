<?php

namespace App\Console\Commands;

use App\Domain\Gradebook\GradebookException;
use App\Services\GradebookMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ReconcileGradebookCommand extends Command
{
    protected $signature = 'smartlms:gradebook-reconcile {--manifest= : Manifest JSON đã dùng backfill}';

    protected $description = 'So sánh source legacy/assessment với shadow Gradebook';

    public function handle(GradebookMigrationService $service): int
    {
        try {
            $path = (string) $this->option('manifest');
            if ($path === '' || ! File::isFile($path)) {
                throw new GradebookException('--manifest phải trỏ tới file JSON tồn tại.');
            }
            $manifest = json_decode(File::get($path), true);
            if (! is_array($manifest)) {
                throw new GradebookException('Manifest JSON không hợp lệ.');
            }
            $result = $service->reconcile($manifest);
        } catch (GradebookException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if (! $result['passed']) {
            $this->error('Reconciliation chưa đạt; không được cutover read source.');

            return self::FAILURE;
        }

        $this->info('Reconciliation đạt trên manifest này. Cutover vẫn cần phê duyệt riêng.');

        return self::SUCCESS;
    }
}
