<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateLearningFilesToPrivateStorage extends Command
{
    protected $signature = 'smartlms:migrate-private-learning-files
        {--dry-run : Chỉ kiểm tra và thống kê, không sao chép hoặc cập nhật dữ liệu}';

    protected $description = 'Di chuyển bài nộp, file bài giảng và học liệu từ public sang storage private đã cấu hình';

    private int $migrated = 0;

    private int $skipped = 0;

    private int $failed = 0;

    /** @var array<int, array{table:string,path:string,disk:string,target:string}> */
    private array $references = [];

    public function handle(): int
    {
        $this->references = [
            [
                'table' => 'assignment_submissions',
                'path' => 'file_path',
                'disk' => 'file_disk',
                'target' => (string) config('filesystems.submission_disk', 'local'),
            ],
            [
                'table' => 'lessons',
                'path' => 'attachment',
                'disk' => 'attachment_disk',
                'target' => (string) config('filesystems.lesson_attachment_disk', 'local'),
            ],
            [
                'table' => 'learning_materials',
                'path' => 'file_path',
                'disk' => 'disk',
                'target' => (string) config('filesystems.lesson_attachment_disk', 'local'),
            ],
        ];

        foreach ($this->references as $reference) {
            if ($reference['target'] === 'public') {
                $this->error("Disk đích của {$reference['table']} vẫn là public. Hãy cấu hình private disk trước.");

                return self::FAILURE;
            }
        }

        foreach ($this->references as $reference) {
            $this->migrateTable($reference);
        }

        $prefix = $this->option('dry-run') ? 'Cần di chuyển' : 'Đã di chuyển';
        $this->info("{$prefix}: {$this->migrated}; bỏ qua: {$this->skipped}; lỗi: {$this->failed}.");

        return $this->failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @param array{table:string,path:string,disk:string,target:string} $reference */
    private function migrateTable(array $reference): void
    {
        if (! Schema::hasTable($reference['table'])
            || ! Schema::hasColumn($reference['table'], $reference['path'])
            || ! Schema::hasColumn($reference['table'], $reference['disk'])) {
            return;
        }

        $this->publicReferences($reference)
            ->select(['id', $reference['path']])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($reference): void {
                foreach ($rows as $row) {
                    $this->migrateReference($reference, (int) $row->id, (string) $row->{$reference['path']});
                }
            });
    }

    /** @param array{table:string,path:string,disk:string,target:string} $reference */
    private function migrateReference(array $reference, int $id, string $path): void
    {
        if ($path === '') {
            $this->skipped++;

            return;
        }

        $source = Storage::disk('public');
        $target = Storage::disk($reference['target']);

        try {
            $sourceExists = $source->exists($path);
            $targetExists = $target->exists($path);

            if (! $sourceExists && ! $targetExists) {
                $this->failed++;
                $this->error("Không tìm thấy file cho {$reference['table']} #{$id}.");

                return;
            }

            if ($sourceExists && $targetExists && ! $this->filesMatch($path, $reference['target'])) {
                $this->failed++;
                $this->error("Xung đột file đích cho {$reference['table']} #{$id}.");

                return;
            }

            if ($this->option('dry-run')) {
                $this->migrated++;

                return;
            }

            if (! $targetExists) {
                $stream = $source->readStream($path);
                if (! is_resource($stream)) {
                    throw new \RuntimeException('Không thể mở file nguồn.');
                }

                try {
                    if (! $target->writeStream($path, $stream)) {
                        throw new \RuntimeException('Không thể ghi file vào private storage.');
                    }
                } finally {
                    fclose($stream);
                }

                if (! $target->exists($path) || ! $this->filesMatch($path, $reference['target'])) {
                    throw new \RuntimeException('File private không khớp với file nguồn sau khi sao chép.');
                }
            }

            DB::table($reference['table'])->where('id', $id)->update([
                $reference['disk'] => $reference['target'],
            ]);

            if ($sourceExists && ! $this->hasRemainingPublicReference($path)) {
                $source->delete($path);
            }

            $this->migrated++;
        } catch (Throwable $exception) {
            $this->failed++;
            $this->error("Không thể di chuyển {$reference['table']} #{$id}: {$exception->getMessage()}");
        }
    }

    private function filesMatch(string $path, string $targetDisk): bool
    {
        $source = Storage::disk('public');
        $target = Storage::disk($targetDisk);

        if ($source->size($path) !== $target->size($path)) {
            return false;
        }

        $sourceStream = $source->readStream($path);
        $targetStream = $target->readStream($path);
        if (! is_resource($sourceStream) || ! is_resource($targetStream)) {
            if (is_resource($sourceStream)) {
                fclose($sourceStream);
            }
            if (is_resource($targetStream)) {
                fclose($targetStream);
            }

            return false;
        }

        try {
            $sourceHash = hash_init('sha256');
            $targetHash = hash_init('sha256');
            hash_update_stream($sourceHash, $sourceStream);
            hash_update_stream($targetHash, $targetStream);

            return hash_final($sourceHash) === hash_final($targetHash);
        } finally {
            fclose($sourceStream);
            fclose($targetStream);
        }
    }

    private function hasRemainingPublicReference(string $path): bool
    {
        foreach ($this->references as $reference) {
            if (! Schema::hasTable($reference['table'])
                || ! Schema::hasColumn($reference['table'], $reference['path'])
                || ! Schema::hasColumn($reference['table'], $reference['disk'])) {
                continue;
            }

            if ($this->publicReferences($reference)->where($reference['path'], $path)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{table:string,path:string,disk:string,target:string}  $reference
     */
    private function publicReferences(array $reference): Builder
    {
        return DB::table($reference['table'])
            ->whereNotNull($reference['path'])
            ->where($reference['path'], '!=', '')
            ->where(function (Builder $query) use ($reference): void {
                $query->where($reference['disk'], 'public')
                    ->orWhereNull($reference['disk'])
                    ->orWhere($reference['disk'], '');
            });
    }
}
