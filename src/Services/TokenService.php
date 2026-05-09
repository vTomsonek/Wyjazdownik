<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Pojedyncze źródło prawdy dla generowania tokenów.
 * Używane przez magic linki, sesje admina, access tokeny uczestników, summary public token.
 *
 * Wszystkie tokeny mają stałą długość 64 znaków hex (bin2hex z 32 bajtów = 256 bitów entropii).
 * Pasuje 1:1 do schema.sql (CHAR(64)).
 */
final class TokenService
{
    public const TOKEN_LENGTH_HEX = 64;
    private const TOKEN_BYTES = 32;

    public static function generate(): string
    {
        return bin2hex(random_bytes(self::TOKEN_BYTES));
    }

    /**
     * Sprawdza czy podany ciąg ma format tokenu (64 znaki hex).
     * Walidacja przed query DB - chroni przed dziwnymi inputami.
     */
    public static function isValid(string $token): bool
    {
        return (bool) preg_match('/^[0-9a-f]{64}$/i', $token);
    }
}
