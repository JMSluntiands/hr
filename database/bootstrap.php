<?php
/**
 * Loads database/db.php safely (same variable scope as caller).
 * Use from entry points that need a clear error instead of HTTP 500.
 *
 * @return array{ok:bool, conn:?mysqli, code:string, message:string}
 */
function hr_load_database_connection(string $dbPhpAbsolutePath): array
{
    if (!extension_loaded('mysqli')) {
        return [
            'ok' => false,
            'conn' => null,
            'code' => 'no_mysqli',
            'message' => 'The PHP mysqli extension is not enabled on this server. Enable mysqli in php.ini (or ask your host).',
        ];
    }

    if (!is_readable($dbPhpAbsolutePath)) {
        return [
            'ok' => false,
            'conn' => null,
            'code' => 'missing_db_file',
            'message' => 'Missing database/db.php. Copy database/db.example.php to database/db.php and set MySQL credentials (db.php is not in Git).',
        ];
    }

    /** @var mysqli|false|null $conn */
    $conn = null;
    include $dbPhpAbsolutePath;

    if (!isset($conn)) {
        return [
            'ok' => false,
            'conn' => null,
            'code' => 'db_file_invalid',
            'message' => 'database/db.php did not set $conn. Fix the file or replace it from database/db.example.php.',
        ];
    }

    if (!($conn instanceof mysqli)) {
        return [
            'ok' => false,
            'conn' => null,
            'code' => 'connect_failed',
            'message' => 'Could not connect to MySQL. Edit database/db.php with the correct host, database name, username, and password for this server.',
        ];
    }

    return ['ok' => true, 'conn' => $conn, 'code' => '', 'message' => ''];
}
