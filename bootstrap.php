<?php
declare(strict_types=1);

/**
 * Bootstrap - environment loading and autoloading.
 *
 * Loads composer's autoloader if available, otherwise registers a minimal
 * PSR-4 autoloader for the App\ namespace. Parses .env into $_ENV / getenv().
 */

define('BASE_PATH', __DIR__);

// ---------------------------------------------------------------------------
// Autoloader: prefer composer, fall back to a tiny PSR-4 loader.
// ---------------------------------------------------------------------------
$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $file = BASE_PATH . '/src/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });

    // Composer's "files" autoload pulls this in normally; mirror that here.
    require BASE_PATH . '/src/Helpers/functions.php';
}

// ---------------------------------------------------------------------------
// .env loader (minimal - works without vlucas/phpdotenv).
// If composer is installed, prefer Dotenv for full feature parity.
// ---------------------------------------------------------------------------
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    if (class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
    } else {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            // Strip surrounding quotes
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last  = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }
            // .env to source-of-truth dla aplikacji - zawsze nadpisujemy.
            // (Apache SetEnv jest tylko dla mod_rewrite, nie dla PHP runtime.)
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// ---------------------------------------------------------------------------
// Global PHP runtime tweaks driven by env.
// ---------------------------------------------------------------------------
date_default_timezone_set(env('APP_TIMEZONE', 'Europe/Warsaw'));

if (env('APP_ENV', 'prod') === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
