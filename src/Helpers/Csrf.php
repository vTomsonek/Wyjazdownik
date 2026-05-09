<?php
declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

/**
 * CSRF token generation and validation.
 *
 * Tokens are stored in the session and rotated per page render.
 * Use Csrf::field() to drop a hidden input into a form, and
 * Csrf::validate() at the top of every POST handler.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        self::ensureSession();
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    /**
     * Validate the token from $_POST['_csrf'] (or X-CSRF-Token header).
     * Throws on mismatch - call before processing any POST.
     */
    public static function validate(?string $submitted = null): void
    {
        self::ensureSession();
        $expected = (string) ($_SESSION[self::SESSION_KEY] ?? '');
        if ($submitted === null) {
            $submitted = (string) ($_POST['_csrf']
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? '');
        }
        if ($expected === '' || !hash_equals($expected, $submitted)) {
            throw new RuntimeException('Nieprawidłowy token CSRF.');
        }
    }

    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
