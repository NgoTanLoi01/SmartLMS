<?php

declare(strict_types=1);

const MYSQL_CONTAINER = 'lms-db';
const POSTGRES_CONTAINER = 'lms-vector-db';

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot.'/.env';
$syncRunningDatabases = in_array('--sync-running-databases', $argv, true);

if (! is_file($envPath)) {
    fwrite(STDERR, ".env không tồn tại. Hãy sao chép từ .env.example trước.\n");
    exit(1);
}

$original = file_get_contents($envPath);
if ($original === false) {
    fwrite(STDERR, "Không thể đọc .env.\n");
    exit(1);
}

$current = parseEnv($original);
$rotated = [
    'APP_ENV' => 'production',
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'APP_DEBUG' => 'false',
    'FORCE_HTTPS' => 'true',
    'LOG_LEVEL' => 'warning',
    'DB_PASSWORD' => randomSecret(32),
    'MYSQL_ROOT_PASSWORD' => randomSecret(40),
    'DB_VECTOR_PASSWORD' => randomSecret(32),
    'SESSION_ENCRYPT' => 'true',
    'SESSION_SECURE_COOKIE' => 'true',
    'REVERB_APP_ID' => (string) random_int(100000, 999999999),
    'REVERB_APP_KEY' => bin2hex(random_bytes(16)),
    'REVERB_APP_SECRET' => bin2hex(random_bytes(32)),
];
$rotated['VITE_REVERB_APP_KEY'] = '"${REVERB_APP_KEY}"';

if ($syncRunningDatabases) {
    syncRunningDatabases($current, $rotated);
}

$updated = updateEnv($original, $rotated);
$temporaryPath = $envPath.'.rotate-'.bin2hex(random_bytes(6));

if (file_put_contents($temporaryPath, $updated, LOCK_EX) === false) {
    fwrite(STDERR, "Không thể ghi file .env tạm.\n");
    exit(1);
}

chmod($temporaryPath, 0600);
if (! rename($temporaryPath, $envPath)) {
    @unlink($temporaryPath);
    fwrite(STDERR, "Không thể cập nhật .env nguyên tử.\n");
    exit(1);
}

echo "Đã rotate APP_KEY, credential database và credential Reverb; APP_DEBUG=false.\n";
echo $syncRunningDatabases
    ? "Credential trong database đang chạy đã được đồng bộ.\n"
    : "Database đang chạy chưa được thay đổi; dùng --sync-running-databases nếu có volume hiện hữu.\n";

function randomSecret(int $bytes): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

/** @return array<string, string> */
function parseEnv(string $contents): array
{
    $values = [];
    foreach (preg_split('/\R/', $contents) ?: [] as $line) {
        if (! preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $line, $matches)) {
            continue;
        }

        $values[$matches[1]] = trim($matches[2], " \t\n\r\0\x0B\"'");
    }

    return $values;
}

/** @param array<string, string> $values */
function updateEnv(string $contents, array $values): string
{
    foreach ($values as $key => $value) {
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
        $replacement = $key.'='.$value;

        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, $replacement, $contents, 1) ?? $contents;
        } else {
            $contents = rtrim($contents).PHP_EOL.$replacement.PHP_EOL;
        }
    }

    return rtrim($contents).PHP_EOL;
}

/**
 * @param array<string, string> $current
 * @param array<string, string> $rotated
 */
function syncRunningDatabases(array $current, array $rotated): void
{
    foreach (['DB_USERNAME', 'DB_VECTOR_USERNAME'] as $key) {
        if (empty($current[$key])) {
            throw new RuntimeException("Thiếu {$key} trong .env.");
        }
    }

    assertContainerRunning(MYSQL_CONTAINER);
    assertContainerRunning(POSTGRES_CONTAINER);

    $oldRootPassword = containerEnv(MYSQL_CONTAINER, 'MYSQL_ROOT_PASSWORD');
    $mysqlUser = sqlString($current['DB_USERNAME']);
    $newMysqlPassword = sqlString($rotated['DB_PASSWORD']);
    $newRootPassword = sqlString($rotated['MYSQL_ROOT_PASSWORD']);
    $oldMysqlPassword = sqlString($current['DB_PASSWORD'] ?? containerEnv(MYSQL_CONTAINER, 'MYSQL_PASSWORD'));
    $oldRootPasswordSql = sqlString($oldRootPassword);
    $mysqlUpdated = false;

    try {
        runCommand(
            ['docker', 'exec', '-i', '-e', 'MYSQL_PWD='.$oldRootPassword, MYSQL_CONTAINER, 'mysql', '-uroot'],
            "ALTER USER '{$mysqlUser}'@'%' IDENTIFIED BY '{$newMysqlPassword}';\n".
            "ALTER USER 'root'@'localhost' IDENTIFIED BY '{$newRootPassword}';\nFLUSH PRIVILEGES;\n"
        );
        $mysqlUpdated = true;

        $postgresUser = pgIdentifier($current['DB_VECTOR_USERNAME']);
        $newPostgresPassword = sqlString($rotated['DB_VECTOR_PASSWORD']);
        runCommand(
            ['docker', 'exec', '-i', POSTGRES_CONTAINER, 'psql', '-v', 'ON_ERROR_STOP=1', '-U', $current['DB_VECTOR_USERNAME'], '-d', $current['DB_VECTOR_DATABASE'] ?? 'lms_ai'],
            "ALTER USER \"{$postgresUser}\" WITH PASSWORD '{$newPostgresPassword}';\n"
        );
    } catch (Throwable $exception) {
        if ($mysqlUpdated) {
            try {
                runCommand(
                    ['docker', 'exec', '-i', '-e', 'MYSQL_PWD='.$rotated['MYSQL_ROOT_PASSWORD'], MYSQL_CONTAINER, 'mysql', '-uroot'],
                    "ALTER USER '{$mysqlUser}'@'%' IDENTIFIED BY '{$oldMysqlPassword}';\n".
                    "ALTER USER 'root'@'localhost' IDENTIFIED BY '{$oldRootPasswordSql}';\nFLUSH PRIVILEGES;\n"
                );
            } catch (Throwable) {
                fwrite(STDERR, "CẢNH BÁO: rollback credential MySQL thất bại.\n");
            }
        }

        fwrite(STDERR, "Rotation database thất bại: {$exception->getMessage()}\n");
        exit(1);
    }
}

function assertContainerRunning(string $container): void
{
    $status = trim(runCommand(['docker', 'inspect', '-f', '{{.State.Running}}', $container]));
    if ($status !== 'true') {
        throw new RuntimeException("Container {$container} không chạy.");
    }
}

function containerEnv(string $container, string $key): string
{
    $output = runCommand(['docker', 'inspect', '-f', '{{range .Config.Env}}{{println .}}{{end}}', $container]);
    foreach (preg_split('/\R/', $output) ?: [] as $line) {
        if (str_starts_with($line, $key.'=')) {
            return substr($line, strlen($key) + 1);
        }
    }

    throw new RuntimeException("Không tìm thấy {$key} trong {$container}.");
}

function sqlString(string $value): string
{
    return str_replace("'", "''", $value);
}

function pgIdentifier(string $value): string
{
    if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
        throw new RuntimeException('DB_VECTOR_USERNAME không hợp lệ.');
    }

    return $value;
}

/** @param list<string> $command */
function runCommand(array $command, string $input = ''): string
{
    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes);

    if (! is_resource($process)) {
        throw new RuntimeException('Không thể chạy command phụ trợ.');
    }

    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]) ?: '';
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        throw new RuntimeException(trim($stderr) ?: 'Command phụ trợ thất bại.');
    }

    return $stdout;
}
