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
        {--group=all : all, submissions, lessons hoặc materials}
        {--dry-run : Chỉ kiểm tra và thống kê, không sao chép hoặc cập nhật dữ liệu}
        {--delete-source : Xóa bản nguồn sau khi mọi reference đã chuyển; mặc định giữ để rollback}';

    protected $description = 'Di chuyển bài nộp, file bài giảng và học liệu từ disk hiện tại sang private storage đã cấu hình';

    private int $migrated = 0;

    private int $skipped = 0;

    private int $failed = 0;

    /** @var array<int, array{group:string,table:string,path:string,disk:string,target:string}> */
    private array $references = [];

    /** @var array<int, array{group:string,table:string,path:string,disk:string,target:string}> */
    private array $referenceCatalog = [];

    public function handle(): int
    {
        $this->references = [
            [
                'group' => 'submissions',
                'table' => 'assignment_submissions',
                'path' => 'file_path',
                'disk' => 'file_disk',
                'target' => (string) config('filesystems.submission_disk', 'local'),
            ],
            [
                'group' => 'lessons',
                'table' => 'lessons',
                'path' => 'attachment',
                'disk' => 'attachment_disk',
                'target' => (string) config('filesystems.lesson_attachment_disk', 'local'),
            ],
            [
                'group' => 'materials',
                'table' => 'learning_materials',
                'path' => 'file_path',
                'disk' => 'disk',
                'target' => (string) config('filesystems.lesson_attachment_disk', 'local'),
            ],
        ];
        $this->referenceCatalog = $this->references;

        $group = (string) $this->option('group');
        if (! in_array($group, ['all', 'submissions', 'lessons', 'materials'], true)) {
            $this->error('Group không hợp lệ. Chọn all, submissions, lessons hoặc materials.');

            return self::FAILURE;
        }
        if ($group !== 'all') {
            $this->references = array_values(array_filter(
                $this->references,
                fn (array $reference): bool => $reference['group'] === $group,
            ));
        }

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

    /** @param array{group:string,table:string,path:string,disk:string,target:string} $reference */
    private function migrateTable(array $reference): void
    {
        if (! Schema::hasTable($reference['table'])
            || ! Schema::hasColumn($reference['table'], $reference['path'])
            || ! Schema::hasColumn($reference['table'], $reference['disk'])) {
            return;
        }

        $this->sourceReferences($reference)
            ->select(['id', $reference['path'], $reference['disk']])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($reference): void {
                foreach ($rows as $row) {
                    $this->migrateReference(
                        $reference,
                        (int) $row->id,
                        (string) $row->{$reference['path']},
                        filled($row->{$reference['disk']}) ? (string) $row->{$reference['disk']} : 'public',
                    );
                }
            });
    }

    /** @param array{group:string,table:string,path:string,disk:string,target:string} $reference */
    private function migrateReference(array $reference, int $id, string $path, string $sourceDisk): void
    {
        if ($path === '') {
            $this->skipped++;

            return;
        }

        try {
            $source = Storage::disk($sourceDisk);
            $target = Storage::disk($reference['target']);
            $sourceExists = $source->exists($path);
            $targetExists = $target->exists($path);

            if (! $sourceExists && ! $targetExists) {
                $this->failed++;
                $this->error("Không tìm thấy file cho {$reference['table']} #{$id}.");

                return;
            }

            if ($sourceExists && $targetExists && ! $this->filesMatch($path, $sourceDisk, $reference['target'])) {
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

                if (! $target->exists($path) || ! $this->filesMatch($path, $sourceDisk, $reference['target'])) {
                    throw new \RuntimeException('File private không khớp với file nguồn sau khi sao chép.');
                }
            }

            $updates = [$reference['disk'] => $reference['target']];
            if ($reference['table'] === 'assignment_submissions'
                && Schema::hasColumn('assignment_submissions', 'checksum_sha256')) {
                $updates['checksum_sha256'] = $this->checksum($target, $path);
            }
            DB::table($reference['table'])->where('id', $id)->update($updates);

            if ($this->option('delete-source')
                && $sourceExists
                && ! $this->hasRemainingSourceReference($path, $sourceDisk)) {
                $source->delete($path);
            }

            $this->migrated++;
        } catch (Throwable $exception) {
            $this->failed++;
            $this->error("Không thể di chuyển {$reference['table']} #{$id}: {$exception->getMessage()}");
        }
    }

    private function filesMatch(string $path, string $sourceDisk, string $targetDisk): bool
    {
        $source = Storage::disk($sourceDisk);
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

    private function hasRemainingSourceReference(string $path, string $sourceDisk): bool
    {
        foreach ($this->referenceCatalog as $reference) {
            if (! Schema::hasTable($reference['table'])
                || ! Schema::hasColumn($reference['table'], $reference['path'])
                || ! Schema::hasColumn($reference['table'], $reference['disk'])) {
                continue;
            }

            if (DB::table($reference['table'])
                ->where($reference['path'], $path)
                ->where(function (Builder $query) use ($reference, $sourceDisk): void {
                    $query->where($reference['disk'], $sourceDisk);
                    if ($sourceDisk === 'public') {
                        $query->orWhereNull($reference['disk'])->orWhere($reference['disk'], '');
                    }
                })
                ->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{group:string,table:string,path:string,disk:string,target:string}  $reference
     */
    private function sourceReferences(array $reference): Builder
    {
        return DB::table($reference['table'])
            ->whereNotNull($reference['path'])
            ->where($reference['path'], '!=', '')
            ->where(function (Builder $query) use ($reference): void {
                $query->where($reference['disk'], '!=', $reference['target'])
                    ->orWhereNull($reference['disk'])
                    ->orWhere($reference['disk'], '');
            });
    }

    private function checksum(object $disk, string $path): string
    {
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            throw new \RuntimeException('Không thể đọc file đích để tạo checksum.');
        }

        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
