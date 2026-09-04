<?php

namespace App\Services;

use App\Models\BackupRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class BackupService
{
    public const FORMAT = 'smartlms-full-backup';

    public const FORMAT_VERSION = 2;

    public const LOCK_NAME = 'smartlms:backup-restore';

    /**
     * Kept for backwards compatibility with the existing command/controller.
     */
    public function runDatabaseBackup(array $options = []): BackupRun
    {
        return $this->runFullBackup($options);
    }

    public function runFullBackup(array $options = []): BackupRun
    {
        $timezone = config('backup.timezone', 'Asia/Ho_Chi_Minh');
        $startedAt = Carbon::now($timezone);
        $backup = BackupRun::create([
            'user_id' => $options['user_id'] ?? null,
            'type' => 'full',
            'status' => 'running',
            'triggered_by' => $options['triggered_by'] ?? 'manual',
            'started_at' => $startedAt,
            'metadata' => [
                'format' => self::FORMAT,
                'format_version' => self::FORMAT_VERSION,
                'connection' => config('database.default'),
                'database' => config('database.connections.'.config('database.default').'.database'),
            ],
        ]);

        $temporaryDirectory = null;
        $localPath = null;
        $archiveCompleted = false;

        try {
            $directory = config('backup.local_directory', storage_path('app/backups'));
            File::ensureDirectoryExists($directory);
            $temporaryDirectory = $directory.DIRECTORY_SEPARATOR.'.building-'.Str::uuid();
            File::ensureDirectoryExists($temporaryDirectory);

            $filename = 'smartlms-full-'.$startedAt->format('Ymd-His').'-'.$backup->id.'.zip';
            $localPath = $directory.DIRECTORY_SEPARATOR.$filename;
            $databasePath = $temporaryDirectory.DIRECTORY_SEPARATOR.'database.sql.gz';
            $this->dumpMysqlDatabase($databasePath);
            $vectorDatabase = $this->dumpVectorDatabase(
                $temporaryDirectory.DIRECTORY_SEPARATOR.'vector-database.jsonl.gz'
            );

            $fileResult = $this->stageImportantFiles($temporaryDirectory.DIRECTORY_SEPARATOR.'files');
            $manifest = [
                'format' => self::FORMAT,
                'version' => self::FORMAT_VERSION,
                'created_at' => $startedAt->toIso8601String(),
                'application' => config('app.name'),
                'database' => [
                    'driver' => config('database.connections.'.config('database.default').'.driver'),
                    'archive_path' => 'database.sql.gz',
                    'size_bytes' => File::size($databasePath),
                    'checksum_sha256' => hash_file('sha256', $databasePath),
                ],
                'vector_database' => $vectorDatabase,
                'files' => $fileResult['files'],
                'missing_files' => $fileResult['missing'],
            ];

            $manifestPath = $temporaryDirectory.DIRECTORY_SEPARATOR.'manifest.json';
            File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->createArchive($localPath, $temporaryDirectory, $manifest, $manifestPath, $databasePath);
            $archiveCompleted = true;

            $finishedAt = Carbon::now($timezone);
            $metadata = array_merge($backup->metadata ?? [], [
                'package_checksum_sha256' => hash_file('sha256', $localPath),
                'database_checksum_sha256' => $manifest['database']['checksum_sha256'],
                'vector_database_included' => (bool) $vectorDatabase,
                'vector_database_rows' => (int) ($vectorDatabase['rows'] ?? 0),
                'included_files' => count($manifest['files']),
                'missing_files' => count($manifest['missing_files']),
                'included_file_bytes' => array_sum(array_column($manifest['files'], 'size_bytes')),
                'integrity_status' => 'not_verified',
            ]);

            $backup->fill([
                'status' => 'success',
                'filename' => $filename,
                'local_path' => $localPath,
                'size_bytes' => File::size($localPath),
                'finished_at' => $finishedAt,
                'duration_seconds' => $backup->started_at->diffInSeconds($finishedAt),
                'metadata' => $metadata,
            ]);

            $uploadDisk = $this->resolveUploadDisk($options);
            if ($uploadDisk) {
                try {
                    $backup->fill($this->uploadToRemoteDisk($uploadDisk, $localPath, $filename));
                } catch (Throwable $uploadException) {
                    report($uploadException);
                    $backup->metadata = array_merge($backup->metadata ?? [], [
                        'remote_upload_error' => $uploadException->getMessage(),
                    ]);
                    $backup->error_message = 'Backup local thành công nhưng upload từ xa thất bại: '.$uploadException->getMessage();
                }
            }

            $backup->save();
            if (empty($options['skip_prune'])) {
                $this->pruneLocalBackups();
            }

            return $backup;
        } catch (Throwable $e) {
            if (! $archiveCompleted && $localPath && File::isFile($localPath)) {
                File::delete($localPath);
            }
            $finishedAt = Carbon::now($timezone);
            $backup->update([
                'status' => 'failed',
                'finished_at' => $finishedAt,
                'duration_seconds' => $backup->started_at?->diffInSeconds($finishedAt),
                'error_message' => $e->getMessage(),
            ]);

            return $backup;
        } finally {
            if ($temporaryDirectory && File::isDirectory($temporaryDirectory)) {
                File::deleteDirectory($temporaryDirectory);
            }
        }
    }

    private function createArchive(
        string $targetPath,
        string $temporaryDirectory,
        array $manifest,
        string $manifestPath,
        string $databasePath
    ): void {
        $archive = new ZipArchive;
        $opened = $archive->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new RuntimeException('Không thể tạo gói backup ZIP. Mã lỗi: '.$opened);
        }

        try {
            $this->addArchiveFile($archive, $databasePath, 'database.sql.gz');
            $this->addArchiveFile($archive, $manifestPath, 'manifest.json');
            if ($manifest['vector_database']) {
                $vectorPath = $temporaryDirectory.DIRECTORY_SEPARATOR.$manifest['vector_database']['archive_path'];
                $this->addArchiveFile($archive, $vectorPath, $manifest['vector_database']['archive_path']);
            }

            foreach ($manifest['files'] as $file) {
                $source = $temporaryDirectory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file['archive_path']);
                $this->addArchiveFile($archive, $source, $file['archive_path']);
            }
        } finally {
            if (! $archive->close()) {
                throw new RuntimeException('Không thể hoàn tất gói backup ZIP.');
            }
        }
    }

    private function addArchiveFile(ZipArchive $archive, string $source, string $archivePath): void
    {
        if (! $archive->addFile($source, $archivePath)) {
            throw new RuntimeException("Không thể thêm {$archivePath} vào gói backup.");
        }
    }

    private function stageImportantFiles(string $targetDirectory): array
    {
        File::ensureDirectoryExists($targetDirectory);
        $references = collect();
        $scanErrors = [];

        foreach ((array) config('backup.file_disks', ['local', 'public']) as $disk) {
            try {
                foreach (Storage::disk($disk)->allFiles() as $path) {
                    $references->push(['disk' => $disk, 'path' => $path]);
                }
            } catch (Throwable $e) {
                report($e);
                $scanErrors[] = [
                    'disk' => $disk,
                    'path' => '*',
                    'error' => 'Không thể liệt kê file trên disk này: '.$e->getMessage(),
                ];
            }
        }

        foreach ($this->databaseFileReferences() as $reference) {
            $references->push($reference);
        }

        $references = $references
            ->map(fn (array $reference) => [
                'disk' => trim((string) ($reference['disk'] ?? '')),
                'path' => $this->normalizeStoragePath((string) ($reference['path'] ?? '')),
            ])
            ->filter(fn (array $reference) => $reference['disk'] !== '' && $reference['path'] !== '')
            ->unique(fn (array $reference) => $reference['disk'].'|'.$reference['path'])
            ->values();

        $files = [];
        $missing = $scanErrors;

        foreach ($references as $reference) {
            $diskName = $reference['disk'];
            $path = $reference['path'];

            try {
                $disk = Storage::disk($diskName);
                if (! $disk->exists($path)) {
                    $missing[] = $reference;

                    continue;
                }

                $archivePath = 'files/'.$this->safeDiskName($diskName).'/'.$path;
                $destination = dirname($targetDirectory).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $archivePath);
                File::ensureDirectoryExists(dirname($destination));
                $sourceStream = $disk->readStream($path);
                if (! is_resource($sourceStream)) {
                    throw new RuntimeException("Không thể đọc {$diskName}://{$path}.");
                }

                $targetStream = fopen($destination, 'wb');
                if (! is_resource($targetStream)) {
                    fclose($sourceStream);
                    throw new RuntimeException("Không thể tạo file tạm cho {$diskName}://{$path}.");
                }

                try {
                    stream_copy_to_stream($sourceStream, $targetStream);
                } finally {
                    fclose($sourceStream);
                    fclose($targetStream);
                }

                $files[] = [
                    'disk' => $diskName,
                    'path' => $path,
                    'archive_path' => $archivePath,
                    'size_bytes' => File::size($destination),
                    'checksum_sha256' => hash_file('sha256', $destination),
                ];
            } catch (Throwable $e) {
                report($e);
                $missing[] = array_merge($reference, ['error' => $e->getMessage()]);
            }
        }

        return ['files' => $files, 'missing' => $missing];
    }

    private function databaseFileReferences(): array
    {
        $sources = [
            ['table' => 'assignment_submissions', 'disk' => 'file_disk', 'path' => 'file_path', 'default_disk' => 'public'],
            ['table' => 'submissions', 'disk' => null, 'path' => 'file_path', 'default_disk' => 'public'],
            ['table' => 'lessons', 'disk' => 'attachment_disk', 'path' => 'attachment', 'default_disk' => 'public'],
            ['table' => 'lessons', 'disk' => 'attachment_disk', 'path' => 'attachment_path', 'default_disk' => 'public'],
            ['table' => 'learning_materials', 'disk' => 'disk', 'path' => 'file_path', 'default_disk' => 'local'],
            ['table' => 'shared_documents', 'disk' => 'disk', 'path' => 'file_path', 'default_disk' => 'r2'],
            ['table' => 'quiz_attempt_attachments', 'disk' => 'disk', 'path' => 'path', 'default_disk' => 'local'],
        ];
        $references = [];

        foreach ($sources as $source) {
            if (! Schema::hasTable($source['table']) || ! Schema::hasColumn($source['table'], $source['path'])) {
                continue;
            }

            $columns = [$source['path']];
            if ($source['disk'] && Schema::hasColumn($source['table'], $source['disk'])) {
                $columns[] = $source['disk'];
            }

            DB::table($source['table'])
                ->whereNotNull($source['path'])
                ->where($source['path'], '!=', '')
                ->select($columns)
                ->orderBy($source['path'])
                ->each(function ($row) use (&$references, $source) {
                    $references[] = [
                        'disk' => ($source['disk'] && isset($row->{$source['disk']}))
                            ? ($row->{$source['disk']} ?: $source['default_disk'])
                            : $source['default_disk'],
                        'path' => $row->{$source['path']},
                    ];
                });
        }

        return $references;
    }

    private function normalizeStoragePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ($path === '' || str_contains("/{$path}/", '/../') || str_contains($path, "\0")) {
            return '';
        }

        return $path;
    }

    private function safeDiskName(string $disk): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $disk);

        return $safe ?: 'unknown';
    }

    private function dumpMysqlDatabase(string $targetPath): void
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}");

        if (! in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Backup hiện chỉ hỗ trợ MySQL/MariaDB.');
        }

        $handle = gzopen($targetPath, 'wb9');
        if (! $handle) {
            throw new RuntimeException('Không thể tạo file backup database.');
        }

        try {
            $pdo = DB::connection($connectionName)->getPdo();
            $database = $connection['database'] ?? '';
            $this->write($handle, "-- SmartLMS database backup\n");
            $this->write($handle, "-- Database: {$database}\n");
            $this->write($handle, '-- Generated at: '.now(config('backup.timezone', 'Asia/Ho_Chi_Minh'))->toDateTimeString()."\n\n");
            $this->write($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            $this->write($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            foreach ($this->tableNames($connectionName) as $table) {
                $quotedTable = $this->quoteIdentifier($table);
                $create = DB::connection($connectionName)->selectOne("SHOW CREATE TABLE {$quotedTable}");
                $createSql = array_values((array) $create)[1] ?? null;
                if (! $createSql) {
                    continue;
                }

                $this->write($handle, "\n-- Table structure for {$quotedTable}\n");
                $this->write($handle, "DROP TABLE IF EXISTS {$quotedTable};\n");
                $this->write($handle, $createSql.";\n\n");
                $this->write($handle, "-- Data for {$quotedTable}\n");
                foreach (DB::connection($connectionName)->table($table)->cursor() as $row) {
                    $values = array_map(fn ($value) => $this->quoteValue($pdo, $value), array_values((array) $row));
                    $this->write($handle, "INSERT INTO {$quotedTable} VALUES (".implode(', ', $values).");\n");
                }
                $this->write($handle, "\n");
            }

            $this->write($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            gzclose($handle);
        }
    }

    private function dumpVectorDatabase(string $targetPath): ?array
    {
        if (! config('backup.include_vector_database', true)) {
            return null;
        }

        $connectionName = config('backup.vector_connection', 'pgsql');
        $connectionConfig = config("database.connections.{$connectionName}");
        if (($connectionConfig['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException('Kết nối vector database phải sử dụng PostgreSQL.');
        }
        if (! Schema::connection($connectionName)->hasTable('document_chunks')) {
            throw new RuntimeException('Không tìm thấy bảng document_chunks trên vector database.');
        }

        $handle = gzopen($targetPath, 'wb9');
        if (! $handle) {
            throw new RuntimeException('Không thể tạo file backup vector database.');
        }

        $rows = 0;
        try {
            $header = [
                'format' => 'smartlms-vector-database',
                'version' => 1,
                'connection' => $connectionName,
                'table' => 'document_chunks',
            ];
            $this->write($handle, json_encode($header, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");

            DB::connection($connectionName)
                ->table('document_chunks')
                ->select([
                    'id', 'course_id', 'uploaded_by', 'document_name', 'content',
                    DB::raw('embedding::text AS embedding'), 'ingestion_id', 'chunk_index',
                    'page_number', 'content_hash', 'is_active', 'created_at', 'updated_at',
                ])
                ->orderBy('id')
                ->each(function ($row) use ($handle, &$rows) {
                    $this->write($handle, json_encode(
                        ['row' => (array) $row],
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                    )."\n");
                    $rows++;
                });
        } finally {
            gzclose($handle);
        }

        return [
            'driver' => 'pgsql',
            'connection' => $connectionName,
            'table' => 'document_chunks',
            'archive_path' => 'vector-database.jsonl.gz',
            'size_bytes' => File::size($targetPath),
            'checksum_sha256' => hash_file('sha256', $targetPath),
            'rows' => $rows,
        ];
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
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function quoteValue(\PDO $pdo, mixed $value): string
    {
        return $value === null ? 'NULL' : $pdo->quote((string) $value);
    }

    private function write($handle, string $content): void
    {
        if (gzwrite($handle, $content) === false) {
            throw new RuntimeException('Không thể ghi dữ liệu vào file backup database.');
        }
    }

    private function resolveUploadDisk(array $options): ?string
    {
        return ! empty($options['upload_r2']) ? 'r2' : (config('backup.disk_upload') ?: null);
    }

    private function uploadToRemoteDisk(string $disk, string $localPath, string $filename): array
    {
        $remoteDirectory = config('backup.remote_directory', 'backups');
        $remotePath = trim($remoteDirectory.'/'.$filename, '/');
        $stream = fopen($localPath, 'rb');
        if (! $stream) {
            throw new RuntimeException('Không thể đọc file backup để upload.');
        }

        try {
            if (! Storage::disk($disk)->put($remotePath, $stream)) {
                throw new RuntimeException('Không thể upload file backup lên kho lưu trữ từ xa.');
            }
        } finally {
            fclose($stream);
        }

        return ['remote_disk' => $disk, 'remote_path' => $remotePath];
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
