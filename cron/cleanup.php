<?php
declare(strict_types=1);

/**
 * Wyjazdownik.pl - cron cleanup.
 *
 * Czysci stare wpisy z tabel ktore puchna w czasie:
 *  - rate_limits (>24h)
 *  - admin_login_tokens (>15min, expired)
 *  - admin_sessions (expired)
 *
 * Cron suggestion (codziennie o 03:00):
 *   0 3 * * * /usr/bin/php /var/www/wyjazdownik/cron/cleanup.php >> /var/log/wyjazdownik-cleanup.log 2>&1
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Database\Connection;

$pdo = Connection::get();
$now = date('c');
$out = static fn(string $msg) => fwrite(STDOUT, "[{$now}] {$msg}\n");

$out('=== Wyjazdownik cleanup ===');

// Rate limits - starsze niz 24h
$stmt = $pdo->exec('DELETE FROM rate_limits WHERE attempted_at < (NOW() - INTERVAL 24 HOUR)');
$out('rate_limits: usunieto ' . (int) $stmt . ' wpisow');

// Magic link tokens - expired (15 min) i juz uzyte
$stmt = $pdo->exec('DELETE FROM admin_login_tokens WHERE expires_at < NOW() OR used_at IS NOT NULL');
$out('admin_login_tokens: usunieto ' . (int) $stmt . ' wpisow');

// Admin sessions - expired
$stmt = $pdo->exec('DELETE FROM admin_sessions WHERE expires_at < NOW()');
$out('admin_sessions: usunieto ' . (int) $stmt . ' wpisow');

$out('=== Cleanup zakonczony ===');
