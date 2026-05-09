<?php
declare(strict_types=1);

/**
 * Wyjazdownik.pl - first-time installation script.
 *
 * Reads .env, creates the database (if missing), runs schema.sql,
 * and optionally loads seed.sql. Designed to be run once on a fresh setup.
 *
 * Run from CLI:
 *     php install.php
 *     php install.php --seed=no
 *
 * Or from a browser (only when APP_ENV=dev):
 *     http://localhost/wyjazdownik/install.php
 */

require __DIR__ . '/bootstrap.php';

$cli         = PHP_SAPI === 'cli';
$includeSeed = true;
$force       = false;

if ($cli) {
    foreach ($argv as $arg) {
        if ($arg === '--seed=no' || $arg === '--no-seed') $includeSeed = false;
        if ($arg === '--force')                           $force       = true;
    }
} else {
    if (env('APP_ENV', 'prod') !== 'dev') {
        http_response_code(403);
        exit('install.php is only accessible from CLI in production.');
    }
    if (isset($_GET['seed']) && $_GET['seed'] === 'no') $includeSeed = false;
    if (isset($_GET['force']))                          $force       = true;
    header('Content-Type: text/plain; charset=UTF-8');
}

$out = static function (string $line) use ($cli): void {
    echo ($cli ? '' : '') . $line . PHP_EOL;
    @ob_flush();
    @flush();
};

$out('=== Wyjazdownik.pl - install ===');
$out('Środowisko: ' . env('APP_ENV', 'prod'));
$out('');

$host = (string) config('database.host');
$port = (int) config('database.port');
$name = (string) config('database.name');
$user = (string) config('database.user');
$pass = (string) config('database.pass');

// 1. Connect. Najpierw próbujemy bezpośrednio do bazy `$name` - to działa zarówno
//    z kontem root (które ma globalny CREATE) jak i z dedykowanym kontem na bazę.
//    Jeśli baza nie istnieje, łączymy się bez dbname i tworzymy ją.
$out("[1/4] Łączę z MySQL ({$user}@{$host}:{$port}) ...");
$pdo = null;
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $out("   OK, połączenie z bazą '{$name}' aktywne.");
} catch (PDOException $e) {
    $msg = $e->getMessage();
    $isUnknownDb = str_contains($msg, 'Unknown database') || (int) $e->getCode() === 1049;
    if (!$isUnknownDb) {
        $out('   BŁĄD: nie można połączyć się z serwerem MySQL.');
        $out('   ' . $msg);
        $out('   Sprawdź dane w .env (DB_HOST, DB_PORT, DB_USER, DB_PASS).');
        exit(1);
    }

    // Baza nie istnieje - łączymy się bez dbname i tworzymy.
    $out("   Baza '{$name}' nie istnieje, próbuję ją utworzyć ...");
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $inner) {
        $out('   BŁĄD: nie można połączyć się z serwerem MySQL: ' . $inner->getMessage());
        exit(1);
    }
}

$out("[2/4] Sprawdzam bazę '{$name}' ...");
try {
    $pdo->exec(
        "CREATE DATABASE IF NOT EXISTS `{$name}` "
        . 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $pdo->exec("USE `{$name}`");
    $out('   OK.');
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Access denied')) {
        $out("   BŁĄD: konto '{$user}' nie ma uprawnień CREATE DATABASE.");
        $out("   Utwórz bazę ręcznie (np. w phpMyAdmin) i uruchom skrypt ponownie.");
    } else {
        $out('   BŁĄD: ' . $e->getMessage());
    }
    exit(1);
}

// 2.5. Bezpiecznik - jesli baza ma juz dane, wymagaj --force
//      (chroni przed przypadkowym DROP wszystkiego na produkcji)
try {
    $check = $pdo->query("SHOW TABLES LIKE 'admins'")->fetch();
    if ($check) {
        $admins = (int) $pdo->query('SELECT COUNT(*) AS c FROM `admins`')->fetch()['c'];
        if ($admins > 0 && !$force) {
            $out('');
            $out('=========================================================');
            $out(' UWAGA: baza zawiera juz dane (admins: ' . $admins . ').');
            $out(' Schema.sql ma DROP TABLE - wszystko zostanie SKASOWANE.');
            $out('');
            $out(' Aby kontynuowac swiadomie:');
            $out('   CLI:        php install.php --force');
            $out('   Browser:    install.php?force=1  (tylko dev)');
            $out('=========================================================');
            exit(1);
        }
    }
} catch (PDOException $e) {
    // Tabela admins jeszcze nie istnieje - ignoruj
}

// 2. Run schema.sql.
$schemaPath = __DIR__ . '/database/schema.sql';
if (!is_file($schemaPath)) {
    $out("BŁĄD: brak pliku {$schemaPath}");
    exit(1);
}
$out('[3/4] Wczytuję schema.sql ...');
$schema     = (string) file_get_contents($schemaPath);
$schemaRuns = runSqlBatch($pdo, $schema);
$out("   OK, wykonano {$schemaRuns} statementów (tabele utworzone).");

// 3. Run seed.sql (optional).
if ($includeSeed) {
    $seedPath = __DIR__ . '/database/seed.sql';
    if (!is_file($seedPath)) {
        $out("UWAGA: brak pliku {$seedPath}, pomijam seed.");
    } else {
        $out('[4/4] Wczytuję seed.sql ...');
        $seed     = (string) file_get_contents($seedPath);
        $seedRuns = runSqlBatch($pdo, $seed);
        $out("   OK, wykonano {$seedRuns} statementów. Dane testowe załadowane:");
        $out('   - Admin: tomasz@jiko.pl');
        $out('   - Wyjazd: "Lato 2026 z ekipą"');
        $out('   - 4 uczestników: Tomek, Kasia, Bartek, Ola');
    }
} else {
    $out('[4/4] Seed pominięty (--seed=no).');
}

$out('');
$out('=== INSTALACJA ZAKOŃCZONA ===');

if ($includeSeed) {
    $base = rtrim((string) env('APP_URL', ''), '/');
    $out('');
    $out('Linki do testowania:');
    $out('  Strona główna:        ' . $base . '/');
    $out('  Wyjazd Tomek:         ' . $base . '/p/1111111111111111111111111111111111111111111111111111111111111111');
    $out('  Wyjazd Kasia:         ' . $base . '/p/2222222222222222222222222222222222222222222222222222222222222222');
    $out('  Wyjazd Bartek:        ' . $base . '/p/3333333333333333333333333333333333333333333333333333333333333333');
    $out('  Wyjazd Ola:           ' . $base . '/p/4444444444444444444444444444444444444444444444444444444444444444');
    $out('  Podsumowanie:         ' . $base . '/summary/cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000cafe0000');
    $out('  Logowanie admina:     ' . $base . '/admin/login');
}

/**
 * Execute a multi-statement SQL string by splitting on semicolons.
 * MySQL's PDO driver doesn't support multi-query natively when emulation is off,
 * so we split safely (skipping semicolons inside string literals) and run each one.
 *
 * @return int Liczba wykonanych statementów (przydatne do diagnostyki).
 */
function runSqlBatch(PDO $pdo, string $sql): int
{
    $statements = splitSqlStatements($sql);
    $count = 0;
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') {
            continue;
        }
        $pdo->exec($stmt);
        $count++;
    }
    return $count;
}

/**
 * @return list<string>
 */
function splitSqlStatements(string $sql): array
{
    // Strip line comments (-- ... \n) i block comments (/* ... */)
    // PRZED splitem, bo inaczej statement zaczynający się od '--' był pomijany.
    $sql = (string) preg_replace('/^\s*--[^\n]*$/m', '', $sql);
    $sql = (string) preg_replace('!/\*.*?\*/!s', '', $sql);

    $statements = [];
    $current    = '';
    $inSingle   = false;
    $inDouble   = false;
    $length     = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';

        if ($char === "'" && $prev !== '\\' && !$inDouble) {
            $inSingle = !$inSingle;
        } elseif ($char === '"' && $prev !== '\\' && !$inSingle) {
            $inDouble = !$inDouble;
        }

        if ($char === ';' && !$inSingle && !$inDouble) {
            if (trim($current) !== '') {
                $statements[] = $current;
            }
            $current = '';
            continue;
        }
        $current .= $char;
    }
    if (trim($current) !== '') {
        $statements[] = $current;
    }
    return $statements;
}
