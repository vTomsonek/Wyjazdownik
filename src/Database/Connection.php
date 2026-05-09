<?php
declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * PDO singleton connection to the application database.
 *
 * Reads credentials from config('database.*') and enforces sensible defaults:
 * exception error mode, native prepared statements, utf8mb4 charset, FETCH_ASSOC.
 */
final class Connection
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function get(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $host    = (string) config('database.host', '127.0.0.1');
        $port    = (int) config('database.port', 3306);
        $name    = (string) config('database.name', 'wyjazdownik');
        $user    = (string) config('database.user', 'root');
        $pass    = (string) config('database.pass', '');
        $charset = (string) config('database.charset', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        ];

        try {
            self::$instance = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        return self::$instance;
    }

    /**
     * For tests: reset the singleton (forces a fresh connection on next get()).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
