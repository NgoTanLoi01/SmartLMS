<?php

namespace App\Services;

use App\Models\BackupRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackupService
{
    public function runDatabaseBackup(array $options = []): BackupRun
    {
        $timezone = config('backup.timezone', 'Asia/Ho_Chi_Minh');
        $startedAt = Carbon::now($timezone);

        $backup = BackupRun::create([
            'user_id' => $options['user_id'] ?? null,
            'type' => 'database',
            'status' => 'running',
            'triggered_by' => $options['triggered_by'] ?? 'manual',
            'started_at' => $startedAt,
            'metadata' => [
                'connection' => config('database.default'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
            ],
        ]);

        try {
            $directory = config('backup.local_directory', storage_path('app/backups'));
            File::ensureDirectoryExists($directory);

            $filename = 'smartlms-db-' . $startedAt->format('Ymd-His') . '.sql.gz';
            $localPath = $directory . DIRECTORY_SEPARATOR . $filename;

            $this->dumpMysqlDatabase($localPath);

            $backup->fill([
                'status' => 'success',
                'filename' => $filename,
                'local_path' => $localPath,
                'size_bytes' => File::size($localPath),
                'finished_at' => Carbon::now($timezone),
            ]);

            $uploadDisk = $this->resolveUploadDisk($options);
            if ($uploadDisk) {
                $backup->fill($this->uploadToRemoteDisk($uploadDisk, $localPath, $filename));
            }

            $backup->duration_seconds = $backup->started_at->diffInSeconds($backup->finished_at);
            $backup->save();

            $this->pruneLocalBackups();

            return $backup;
        } catch (Throwable $e) {
            $finishedAt = Carbon::now($timezone);

            $backup->update([
                'status' => 'failed',
                'finished_at' => $finishedAt,
                'duration_seconds' => $backup->started_at?->diffInSeconds($finishedAt),
                'error_message' => $e->getMessage(),
            ]);

            return $backup;
        }
    }

    private function dumpMysqlDatabase(string $targetPath): void
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}");

        if (!in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException('Backup hiện chỉ hỗ trợ MySQL/MariaDB.');
        }

        $handle = gzopen($targetPath, 'wb9');
        if (!$handle) {
            throw new \RuntimeException('Không thể tạo file backup.');
        }

        try {
            $pdo = DB::connection($connectionName)->getPdo();
            $database = $connection['database'] ?? '';

            $this->write($handle, "-- SmartLMS database backup\n");
            $this->write($handle, "-- Database: {$database}\n");
            $this->write($handle, "-- Generated at: " . now(config('backup.timezone', 'Asia/Ho_Chi_Minh'))->toDateTimeString() . "\n\n");
            $this->write($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            $this->write($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            foreach ($this->tableNames($connectionName) as $table) {
                $quotedTable = $this->quoteIdentifier($table);
                $create = DB::connection($connectionName)->selectOne("SHOW CREATE TABLE {$quotedTable}");
                $createSql = array_values((array) $create)[1] ?? null;

                if (!$createSql) {
                    continue;
                }

                $this->write($handle, "\n-- Table structure for {$quotedTable}\n");
                $this->write($handle, "DROP TABLE IF EXISTS {$quotedTable};\n");
                $this->write($handle, $createSql . ";\n\n");

                $this->write($handle, "-- Data for {$quotedTable}\n");
                foreach (DB::connection($connectionName)->table($table)->cursor() as $row) {
                    $values = array_map(
                        fn ($value) => $this->quoteValue($pdo, $value),
                        array_values((array) $row)
                    );

                    $this->write($handle, "INSERT INTO {$quotedTable} VALUES (" . implode(', ', $values) . ");\n");
                }

                $this->write($handle, "\n");
            }

            $this->write($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            gzclose($handle);
        }
    }

    private function tableNames(string $connectionName): array
    {
        return collect(DB::connection($connectionName)->select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"'))
            ->map(fn ($row) => array_values((array) $row)[0] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function quoteValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return $pdo->quote((string) $value);
    }

    private function write($handle, string $content): void
    {
        gzwrite($handle, $content);
    }

    private function resolveUploadDisk(array $options): ?string
    {
        if (!empty($options['upload_r2'])) {
            return 'r2';
        }

        return config('backup.disk_upload') ?: null;
    }

    private function uploadToRemoteDisk(string $disk, string $localPath, string $filename): array
    {
        $remoteDirectory = config('backup.remote_directory', 'backups');
        $remotePath = trim($remoteDirectory . '/' . $filename, '/');

        $stream = fopen($localPath, 'rb');
        if (!$stream) {
            throw new \RuntimeException('Không thể đọc file backup để upload.');
        }

        try {
            Storage::disk($disk)->put($remotePath, $stream);
        } finally {
            fclose($stream);
        }

        return [
            'remote_disk' => $disk,
            'remote_path' => $remotePath,
        ];
    }

    private function pruneLocalBackups(): void
    {
        $keep = max(1, (int) config('backup.keep_local_copies', 10));

        BackupRun::query()
            ->where('status', 'success')
            ->whereNotNull('local_path')
            ->orderByDesc('finished_at')
            ->skip($keep)
            ->take(100)
            ->get()
            ->each(function (BackupRun $backup) {
                if ($backup->localFileExists()) {
                    File::delete($backup->local_path);
                }
            });
    }
}
