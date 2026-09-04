<?php

namespace App\Services;

use App\Models\BackupRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class BackupRestoreService
{
    public function verifyBackup(BackupRun $backup, bool $persist = true): array
    {
        $result = [
            'valid' => false,
            'message' => 'Gói backup không hợp lệ.',
            'files' => 0,
            'missing_files' => 0,
        ];

        try {
            [$path, $cleanup] = $this->materialize($backup);

            try {
                $result = $this->inspectPackage($path, $backup);
            } finally {
                $cleanup();
            }
        } catch (Throwable $e) {
            report($e);
            $result['message'] = $e->getMessage();
        }

        if ($persist) {
            $metadata = array_merge($backup->metadata ?? [], [
                'integrity_status' => $result['valid'] ? 'valid' : 'invalid',
                'last_verified_at' => now()->toIso8601String(),
                'integrity_message' => $result['message'],
            ]);
            $backup->update(['metadata' => $metadata]);
        }

        return $result;
    }

    public function restoreBackup(BackupRun $backup): array
    {
        [$path, $cleanup] = $this->materialize($backup);
        $temporaryDirectory = null;

        try {
            $verification = $this->inspectPackage($path, $backup);
            if (! $verification['valid']) {
                throw new RuntimeException($verification['message']);
            }

            $archive = new ZipArchive;
            $opened = $archive->open($path, ZipArchive::RDONLY | ZipArchive::CHECKCONS);
            if ($opened !== true) {
                throw new RuntimeException('Không thể mở gói backup để khôi phục.');
            }

            try {
                $manifest = $this->readManifest($archive);
                $temporaryDirectory = rtrim(config('backup.local_directory', storage_path('app/backups')), DIRECTORY_SEPARATOR)
                    .DIRECTORY_SEPARATOR.'.restoring-'.Str::uuid();
                File::ensureDirectoryExists($temporaryDirectory);
                $databasePath = $temporaryDirectory.DIRECTORY_SEPARATOR.'database.sql.gz';
                $this->copyArchiveEntryToFile($archive, $manifest['database']['archive_path'], $databasePath);

                $this->restoreDatabase($databasePath);
                DB::purge(config('database.default'));
                DB::reconnect(config('database.default'));
                $restoredVectorRows = 0;
                if ($manifest['vector_database'] ?? null) {
                    $vectorPath = $temporaryDirectory.DIRECTORY_SEPARATOR.'vector-database.jsonl.gz';
                    $this->copyArchiveEntryToFile($archive, $manifest['vector_database']['archive_path'], $vectorPath);
                    $restoredVectorRows = $this->restoreVectorDatabase($vectorPath, $manifest['vector_database']);
                }
                $restoredFiles = $this->restoreFiles($archive, $manifest['files']);

                return [
                    'restored_files' => $restoredFiles,
                    'restored_vector_rows' => $restoredVectorRows,
                    'missing_files_at_backup' => count($manifest['missing_files'] ?? []),
                    'manifest' => $manifest,
                ];
            } finally {
                $archive->close();
            }
        } finally {
            if ($temporaryDirectory && File::isDirectory($temporaryDirectory)) {
                File::deleteDirectory($temporaryDirectory);
            }
            $cleanup();
        }
    }

    private function inspectPackage(string $path, BackupRun $backup): array
    {
        if (! str_ends_with(strtolower((string) $backup->filename), '.zip')) {
            return $this->invalid('Backup database định dạng cũ chỉ có thể tải xuống, không thể phục hồi từ giao diện.');
        }

        $expectedPackageChecksum = $backup->metadata['package_checksum_sha256'] ?? null;
        if ($expectedPackageChecksum && ! hash_equals($expectedPackageChecksum, hash_file('sha256', $path))) {
            return $this->invalid('Checksum của gói backup không khớp. File có thể đã bị thay đổi hoặc hư hỏng.');
        }

        $archive = new ZipArchive;
        $opened = $archive->open($path, ZipArchive::RDONLY | ZipArchive::CHECKCONS);
        if ($opened !== true) {
            return $this->invalid('Không thể mở gói backup ZIP hoặc cấu trúc ZIP đã bị hỏng.');
        }

        try {
            $manifest = $this->readManifest($archive);
            if (($manifest['format'] ?? null) !== BackupService::FORMAT
                || (int) ($manifest['version'] ?? 0) !== BackupService::FORMAT_VERSION) {
                return $this->invalid('Phiên bản hoặc định dạng gói backup không được hỗ trợ.');
            }

            $currentDriver = config('database.connections.'.config('database.default').'.driver');
            if (! in_array($currentDriver, ['mysql', 'mariadb'], true)
                || ! in_array($manifest['database']['driver'] ?? null, ['mysql', 'mariadb'], true)) {
                return $this->invalid('Gói phục hồi và database hiện tại phải sử dụng MySQL/MariaDB.');
            }

            $databaseCheck = $this->verifyArchiveEntry($archive, $manifest['database']);
            if ($databaseCheck !== null) {
                return $this->invalid('Database backup không hợp lệ: '.$databaseCheck);
            }

            if (! $this->hasValidGzipHeader($archive, $manifest['database']['archive_path'])) {
                return $this->invalid('File database trong gói backup không phải gzip hợp lệ.');
            }

            if ($manifest['vector_database'] ?? null) {
                $vectorDatabase = $manifest['vector_database'];
                $configuredConnection = config('backup.vector_connection', 'pgsql');
                if (($vectorDatabase['driver'] ?? null) !== 'pgsql'
                    || ($vectorDatabase['connection'] ?? null) !== $configuredConnection
                    || (config("database.connections.{$configuredConnection}.driver") ?? null) !== 'pgsql') {
                    return $this->invalid('Cấu hình vector database trong backup không khớp với hệ thống hiện tại.');
                }

                $vectorCheck = $this->verifyArchiveEntry($archive, $vectorDatabase);
                if ($vectorCheck !== null) {
                    return $this->invalid('Vector database backup không hợp lệ: '.$vectorCheck);
                }
                if (! $this->hasValidGzipHeader($archive, $vectorDatabase['archive_path'])) {
                    return $this->invalid('File vector database trong gói backup không phải gzip hợp lệ.');
                }
            }

            foreach ($manifest['files'] ?? [] as $file) {
                if (! $this->validStorageReference($file)) {
                    return $this->invalid('Manifest chứa đường dẫn file không an toàn.');
                }

                $fileCheck = $this->verifyArchiveEntry($archive, $file);
                if ($fileCheck !== null) {
                    return $this->invalid("File {$file['archive_path']} không hợp lệ: {$fileCheck}");
                }
            }

            $missing = count($manifest['missing_files'] ?? []);

            return [
                'valid' => true,
                'message' => $missing > 0
                    ? "Gói backup toàn vẹn; có {$missing} file nguồn đã bị thiếu tại thời điểm tạo backup."
                    : 'Gói backup toàn vẹn và sẵn sàng phục hồi.',
                'files' => count($manifest['files'] ?? []),
                'missing_files' => $missing,
            ];
        } finally {
            $archive->close();
        }
    }

    private function readManifest(ZipArchive $archive): array
    {
        $stat = $archive->statName('manifest.json');
        if (! $stat || ($stat['size'] ?? 0) > 10 * 1024 * 1024) {
            throw new RuntimeException('Không tìm thấy manifest hợp lệ trong gói backup.');
        }

        $json = $archive->getFromName('manifest.json');
        if ($json === false) {
            throw new RuntimeException('Không thể đọc manifest trong gói backup.');
        }

        $manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($manifest) || ! is_array($manifest['database'] ?? null) || ! is_array($manifest['files'] ?? null)) {
            throw new RuntimeException('Cấu trúc manifest trong gói backup không hợp lệ.');
        }

        return $manifest;
    }

    private function verifyArchiveEntry(ZipArchive $archive, array $entry): ?string
    {
        $archivePath = $entry['archive_path'] ?? '';
        $expectedChecksum = $entry['checksum_sha256'] ?? '';
        $expectedSize = $entry['size_bytes'] ?? null;
        if (! $this->validArchivePath($archivePath) || ! preg_match('/^[a-f0-9]{64}$/i', (string) $expectedChecksum)) {
            return 'metadata checksum hoặc đường dẫn không hợp lệ';
        }

        $stat = $archive->statName($archivePath);
        if (! $stat) {
            return 'không tìm thấy trong ZIP';
        }
        if ($expectedSize !== null && (int) $expectedSize !== (int) ($stat['size'] ?? -1)) {
            return 'dung lượng không khớp';
        }

        $stream = $archive->getStream($archivePath);
        if (! is_resource($stream)) {
            return 'không thể đọc nội dung';
        }

        $hash = hash_init('sha256');
        try {
            hash_update_stream($hash, $stream);
        } finally {
            fclose($stream);
        }

        return hash_equals(strtolower($expectedChecksum), hash_final($hash)) ? null : 'checksum không khớp';
    }

    private function hasValidGzipHeader(ZipArchive $archive, string $archivePath): bool
    {
        $stream = $archive->getStream($archivePath);
        if (! is_resource($stream)) {
            return false;
        }

        try {
            return fread($stream, 2) === "\x1f\x8b";
        } finally {
            fclose($stream);
        }
    }

    private function restoreDatabase(string $databasePath): void
    {
        $handle = gzopen($databasePath, 'rb');
        if (! $handle) {
            throw new RuntimeException('Không thể giải nén database backup.');
        }

        $statement = '';
        $quote = null;
        $escaped = false;

        try {
            while (! gzeof($handle)) {
                $line = gzgets($handle);
                if ($line === false) {
                    break;
                }
                if ($quote === null && trim($statement) === '' && str_starts_with(ltrim($line), '--')) {
                    continue;
                }

                $length = strlen($line);
                for ($index = 0; $index < $length; $index++) {
                    $character = $line[$index];

                    if ($quote !== null) {
                        $statement .= $character;
                        if ($escaped) {
                            $escaped = false;

                            continue;
                        }
                        if ($character === '\\' && $quote !== '`') {
                            $escaped = true;

                            continue;
                        }
                        if ($character === $quote) {
                            if (($line[$index + 1] ?? null) === $quote) {
                                $statement .= $line[++$index];
                            } else {
                                $quote = null;
                            }
                        }

                        continue;
                    }

                    if (in_array($character, ["'", '"', '`'], true)) {
                        $quote = $character;
                        $statement .= $character;

                        continue;
                    }

                    if ($character === ';') {
                        if (trim($statement) !== '') {
                            DB::unprepared($statement);
                        }
                        $statement = '';

                        continue;
                    }

                    $statement .= $character;
                }
            }

            if (trim($statement) !== '') {
                DB::unprepared($statement);
            }
        } finally {
            gzclose($handle);
            rescue(fn () => DB::unprepared('SET FOREIGN_KEY_CHECKS=1'), report: true);
        }
    }

    private function restoreVectorDatabase(string $databasePath, array $metadata): int
    {
        $connectionName = config('backup.vector_connection', 'pgsql');
        if (($metadata['connection'] ?? null) !== $connectionName
            || (config("database.connections.{$connectionName}.driver") ?? null) !== 'pgsql') {
            throw new RuntimeException('Cấu hình vector database không phù hợp để phục hồi.');
        }

        $handle = gzopen($databasePath, 'rb');
        if (! $handle) {
            throw new RuntimeException('Không thể giải nén vector database backup.');
        }

        try {
            $headerLine = gzgets($handle);
            $header = is_string($headerLine) ? json_decode($headerLine, true, 32, JSON_THROW_ON_ERROR) : null;
            if (($header['format'] ?? null) !== 'smartlms-vector-database'
                || (int) ($header['version'] ?? 0) !== 1
                || ($header['table'] ?? null) !== 'document_chunks') {
                throw new RuntimeException('Header vector database backup không hợp lệ.');
            }

            $connection = DB::connection($connectionName);
            $allowedColumns = [
                'id', 'course_id', 'uploaded_by', 'document_name', 'content', 'embedding',
                'ingestion_id', 'chunk_index', 'page_number', 'content_hash', 'is_active',
                'created_at', 'updated_at',
            ];
            $restored = 0;

            $connection->transaction(function () use ($connection, $handle, $allowedColumns, $metadata, &$restored) {
                $connection->table('document_chunks')->delete();

                while (! gzeof($handle)) {
                    $line = gzgets($handle);
                    if ($line === false || trim($line) === '') {
                        continue;
                    }

                    $payload = json_decode($line, true, 32, JSON_THROW_ON_ERROR);
                    $row = $payload['row'] ?? null;
                    if (! is_array($row) || array_diff(array_keys($row), $allowedColumns) !== []) {
                        throw new RuntimeException('Vector database backup chứa dòng dữ liệu không hợp lệ.');
                    }
                    if (isset($row['embedding'])
                        && ! preg_match('/^\[[0-9eE+,.\-\s]+\]$/', (string) $row['embedding'])) {
                        throw new RuntimeException('Vector database backup chứa embedding không hợp lệ.');
                    }

                    $columns = array_values(array_filter(
                        $allowedColumns,
                        fn (string $column) => array_key_exists($column, $row)
                    ));
                    $quotedColumns = array_map(fn (string $column) => '"'.$column.'"', $columns);
                    $placeholders = array_map(fn (string $column) => $column === 'embedding' ? '?::vector' : '?', $columns);
                    $bindings = array_map(fn (string $column) => $row[$column], $columns);
                    $connection->statement(
                        'INSERT INTO "document_chunks" ('.implode(', ', $quotedColumns).') VALUES ('.implode(', ', $placeholders).')',
                        $bindings
                    );
                    $restored++;
                }

                if ($restored !== (int) ($metadata['rows'] ?? -1)) {
                    throw new RuntimeException('Số dòng vector database phục hồi không khớp manifest.');
                }

                $connection->statement(
                    "SELECT setval(pg_get_serial_sequence('document_chunks', 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM document_chunks"
                );
            });

            return $restored;
        } finally {
            gzclose($handle);
        }
    }

    private function restoreFiles(ZipArchive $archive, array $files): int
    {
        $restored = 0;

        foreach ($files as $file) {
            if (! $this->validStorageReference($file)) {
                throw new RuntimeException('Manifest chứa đường dẫn file không an toàn.');
            }

            $stream = $archive->getStream($file['archive_path']);
            if (! is_resource($stream)) {
                throw new RuntimeException("Không thể đọc {$file['archive_path']} từ gói backup.");
            }

            try {
                if (! Storage::disk($file['disk'])->writeStream($file['path'], $stream)) {
                    throw new RuntimeException("Không thể phục hồi {$file['disk']}://{$file['path']}.");
                }
            } finally {
                fclose($stream);
            }

            $restored++;
        }

        return $restored;
    }

    private function validStorageReference(array $file): bool
    {
        $disk = $file['disk'] ?? '';
        $path = $file['path'] ?? '';
        $expectedArchivePath = 'files/'.$this->safeDiskName($disk).'/'.$path;

        return is_string($disk)
            && array_key_exists($disk, config('filesystems.disks', []))
            && is_string($path)
            && $this->validArchivePath($path)
            && ($file['archive_path'] ?? null) === $expectedArchivePath;
    }

    private function validArchivePath(mixed $path): bool
    {
        return is_string($path)
            && $path !== ''
            && ! str_starts_with($path, '/')
            && ! str_contains($path, '\\')
            && ! str_contains("/{$path}/", '/../')
            && ! str_contains($path, "\0");
    }

    private function safeDiskName(string $disk): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $disk);

        return $safe ?: 'unknown';
    }

    private function copyArchiveEntryToFile(ZipArchive $archive, string $archivePath, string $target): void
    {
        $source = $archive->getStream($archivePath);
        if (! is_resource($source)) {
            throw new RuntimeException("Không thể đọc {$archivePath} trong gói backup.");
        }
        $destination = fopen($target, 'wb');
        if (! is_resource($destination)) {
            fclose($source);
            throw new RuntimeException('Không thể tạo file database tạm để phục hồi.');
        }

        try {
            stream_copy_to_stream($source, $destination);
        } finally {
            fclose($source);
            fclose($destination);
        }
    }

    private function materialize(BackupRun $backup): array
    {
        if (! $backup->isSuccessful()) {
            throw new RuntimeException('Chỉ có thể kiểm tra hoặc phục hồi backup đã tạo thành công.');
        }

        if ($backup->localFileExists()) {
            return [$backup->local_path, static fn () => null];
        }

        if (! $backup->remote_disk || ! $backup->remote_path) {
            throw new RuntimeException('Không tìm thấy file backup ở local hoặc kho lưu trữ từ xa.');
        }

        $disk = Storage::disk($backup->remote_disk);
        if (! $disk->exists($backup->remote_path)) {
            throw new RuntimeException('Không tìm thấy file backup trên kho lưu trữ từ xa.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'smartlms-backup-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Không thể tạo file tạm để đọc backup từ xa.');
        }

        $source = $disk->readStream($backup->remote_path);
        $destination = fopen($temporaryPath, 'wb');
        if (! is_resource($source) || ! is_resource($destination)) {
            is_resource($source) && fclose($source);
            is_resource($destination) && fclose($destination);
            File::delete($temporaryPath);
            throw new RuntimeException('Không thể tải file backup từ kho lưu trữ từ xa.');
        }

        try {
            stream_copy_to_stream($source, $destination);
        } finally {
            fclose($source);
            fclose($destination);
        }

        return [$temporaryPath, static fn () => File::delete($temporaryPath)];
    }

    private function invalid(string $message): array
    {
        return ['valid' => false, 'message' => $message, 'files' => 0, 'missing_files' => 0];
    }
}
