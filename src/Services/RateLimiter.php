<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

/**
 * Rate limiter oparty o tabelę `rate_limits`.
 *
 * Każda próba zapisuje się jako wiersz z bucket_key (np. "login:192.168.0.1") i timestampem.
 * Sprawdzanie limitu liczy wiersze w oknie czasowym - jeśli przekroczone, throw.
 *
 * Stare wpisy są okresowo czyszczone w cleanup() - można wywoływać z cron'a lub po przekroczeniu limitu.
 */
final class RateLimiter
{
    public function __construct(
        private readonly string $bucketKey,
        private readonly int $maxAttempts,
        private readonly int $windowMinutes,
    ) {
    }

    public static function forLogin(string $ip): self
    {
        return new self(
            bucketKey:     'login:' . $ip,
            maxAttempts:   (int) config('security.login_max_attempts', 3),
            windowMinutes: (int) config('security.login_window_minutes', 15),
        );
    }

    public static function forSubmit(string $identifier): self
    {
        return new self(
            bucketKey:     'submit:' . $identifier,
            maxAttempts:   (int) config('security.submit_max_attempts', 30),
            windowMinutes: (int) config('security.submit_window_minutes', 60),
        );
    }

    /**
     * Liczy próby w aktualnym oknie. Zwraca pozostałe próby (>=0).
     */
    public function attemptsLeft(): int
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS c FROM rate_limits
             WHERE bucket_key = :k AND attempted_at > (NOW() - INTERVAL :w MINUTE)'
        );
        $stmt->bindValue('k', $this->bucketKey);
        $stmt->bindValue('w', $this->windowMinutes, \PDO::PARAM_INT);
        $stmt->execute();
        $used = (int) ($stmt->fetch()['c'] ?? 0);
        return max(0, $this->maxAttempts - $used);
    }

    public function isBlocked(): bool
    {
        return $this->attemptsLeft() === 0;
    }

    /**
     * Zarejestruj próbę. Zwraca true jeśli próba mieści się w limicie.
     */
    public function hit(): bool
    {
        if ($this->isBlocked()) {
            return false;
        }
        $pdo = Connection::get();
        $stmt = $pdo->prepare('INSERT INTO rate_limits (bucket_key) VALUES (:k)');
        $stmt->execute(['k' => $this->bucketKey]);
        return true;
    }

    /**
     * Resetuj licznik (np. po udanym logowaniu).
     */
    public function reset(): void
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare('DELETE FROM rate_limits WHERE bucket_key = :k');
        $stmt->execute(['k' => $this->bucketKey]);
    }

    /**
     * Sprzątanie starych wpisów spoza okna czasowego (best-effort, nie krytyczne).
     */
    public static function cleanup(): void
    {
        $pdo = Connection::get();
        $pdo->exec('DELETE FROM rate_limits WHERE attempted_at < (NOW() - INTERVAL 24 HOUR)');
    }
}
