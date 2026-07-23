<?php

namespace App\Console\Commands;

use App\Services\HtmlSanitizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SanitizeLearningHtmlCommand extends Command
{
    protected $signature = 'smartlms:sanitize-learning-html {--dry-run : Chỉ thống kê, không cập nhật dữ liệu}';

    protected $description = 'Làm sạch HTML đã lưu trong nội dung bài học và hướng dẫn bài tập';

    public function handle(HtmlSanitizer $sanitizer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $changed = [
            'lessons' => $this->sanitizeTable('lessons', 'content', $sanitizer, $dryRun),
            'assignments' => $this->sanitizeTable('assignments', 'instructions', $sanitizer, $dryRun),
        ];

        $prefix = $dryRun ? 'Cần làm sạch' : 'Đã làm sạch';
        $this->info("{$prefix} {$changed['lessons']} bài học và {$changed['assignments']} bài tập.");

        return self::SUCCESS;
    }

    private function sanitizeTable(string $table, string $column, HtmlSanitizer $sanitizer, bool $dryRun): int
    {
        $changed = 0;

        DB::table($table)
            ->select(['id', $column])
            ->whereNotNull($column)
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $column, $sanitizer, $dryRun, &$changed) {
                foreach ($rows as $row) {
                    $original = (string) $row->{$column};
                    $sanitized = $sanitizer->sanitize($original);

                    if ($sanitized === $original) {
                        continue;
                    }

                    $changed++;
                    if (! $dryRun) {
                        DB::table($table)->where('id', $row->id)->update([$column => $sanitized]);
                    }
                }
            });

        return $changed;
    }
}
