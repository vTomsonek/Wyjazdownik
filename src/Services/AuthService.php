<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use App\Models\Admin;

/**
 * Logika logowania admina - magic link + sesje DB.
 *
 * Flow:
 *   1. POST /admin/login z emailem
 *      -> generuj token, zapisz w admin_login_tokens (expires_at = NOW + 15 min)
 *      -> wyślij magic link mailem (lub do logu w dev)
 *   2. GET /admin/auth?token=XXX
 *      -> waliduj token (istnieje, nie expired, nie used)
 *      -> oznacz token jako used_at = NOW
 *      -> utwórz wiersz w admin_sessions z session_token (cookie)
 *      -> ustaw cookie 'admin_session', redirect do /admin
 *   3. GET /admin/logout
 *      -> usuń sesję z DB, skasuj cookie
 *
 * Sesja używa cookie 'admin_session' z 64-hex tokenem. Każdy request weryfikuje
 * cookie -> DB row -> Admin object (currentAdmin()).
 */
final class AuthService
{
    private const COOKIE_NAME = 'admin_session';

    /**
     * Wysyła magic link na podany email.
     * Jeśli konto nie istnieje - tworzy nowe (auto-rejestracja).
     * Każdy z poprawnym emailem może zostać adminem swoich wyjazdów.
     */
    public function sendMagicLink(string $email, MailerService $mailer): bool
    {
        $admin = Admin::findOrCreate($email);

        $token   = TokenService::generate();
        $minutes = (int) config('security.magic_link_lifetime_minutes', 15);

        $pdo  = Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO admin_login_tokens (admin_id, token, expires_at)
             VALUES (:admin_id, :token, DATE_ADD(NOW(), INTERVAL :m MINUTE))'
        );
        $stmt->bindValue('admin_id', $admin->id, \PDO::PARAM_INT);
        $stmt->bindValue('token', $token);
        $stmt->bindValue('m', $minutes, \PDO::PARAM_INT);
        $stmt->execute();

        $magicLink = url('/admin/auth?token=' . $token);

        $subject = 'Zaloguj się do Wyjazdownik.pl';
        $html = $this->renderMagicLinkEmail($admin->name, $magicLink, $minutes);
        $plain = "Cześć {$admin->name}!\n\n"
               . "Kliknij w link żeby się zalogować (ważny przez {$minutes} minut):\n"
               . $magicLink . "\n\n"
               . "Jeśli to nie ty próbowałeś się logować - zignoruj tę wiadomość.";

        $mailer->send($admin->email, $subject, $html, $plain, [
            'magic_link' => $magicLink,
        ]);

        return true;
    }

    /**
     * Weryfikuje token z magic linka, tworzy sesję, ustawia cookie.
     * Zwraca Admin obj jeśli sukces, null jeśli token zły/wygasły/zużyty.
     */
    public function authenticateByToken(string $token, string $ip, string $userAgent): ?Admin
    {
        if (!TokenService::isValid($token)) {
            return null;
        }

        $pdo  = Connection::get();
        $stmt = $pdo->prepare(
            'SELECT id, admin_id, expires_at, used_at FROM admin_login_tokens
             WHERE token = :token LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        if ($row['used_at'] !== null) {
            return null;
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            return null;
        }

        // Mark token as used (one-time)
        $update = $pdo->prepare('UPDATE admin_login_tokens SET used_at = NOW() WHERE id = :id');
        $update->execute(['id' => (int) $row['id']]);

        $admin = Admin::findById((int) $row['admin_id']);
        if ($admin === null) {
            return null;
        }

        // Create session
        $sessionToken = TokenService::generate();
        $hours        = (int) config('security.session_lifetime_hours', 24);

        $insSession = $pdo->prepare(
            'INSERT INTO admin_sessions (admin_id, session_token, expires_at, ip_address, user_agent)
             VALUES (:admin_id, :token, DATE_ADD(NOW(), INTERVAL :h HOUR), :ip, :ua)'
        );
        $insSession->bindValue('admin_id', $admin->id, \PDO::PARAM_INT);
        $insSession->bindValue('token', $sessionToken);
        $insSession->bindValue('h', $hours, \PDO::PARAM_INT);
        $insSession->bindValue('ip', $ip);
        $insSession->bindValue('ua', substr($userAgent, 0, 255));
        $insSession->execute();

        $this->setCookie($sessionToken, $hours);
        return $admin;
    }

    /**
     * Zwraca aktualnie zalogowanego admina (lub null).
     * Sprawdza cookie -> admin_sessions -> admins.
     */
    public function currentAdmin(): ?Admin
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache === false ? null : $cache;
        }

        $token = (string) ($_COOKIE[self::COOKIE_NAME] ?? '');
        if ($token === '' || !TokenService::isValid($token)) {
            $cache = false;
            return null;
        }

        $pdo  = Connection::get();
        $stmt = $pdo->prepare(
            'SELECT s.admin_id FROM admin_sessions s
             WHERE s.session_token = :token AND s.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        if (!$row) {
            $cache = false;
            return null;
        }

        $admin = Admin::findById((int) $row['admin_id']);
        $cache = $admin ?: false;
        return $admin;
    }

    public function isLoggedIn(): bool
    {
        return $this->currentAdmin() !== null;
    }

    public function logout(): void
    {
        $token = (string) ($_COOKIE[self::COOKIE_NAME] ?? '');
        if ($token !== '' && TokenService::isValid($token)) {
            $pdo  = Connection::get();
            $stmt = $pdo->prepare('DELETE FROM admin_sessions WHERE session_token = :t');
            $stmt->execute(['t' => $token]);
        }
        // Skasuj cookie
        setcookie(self::COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $this->cookieSecure(),
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Wymusza zalogowanie - jeśli admin nie zalogowany, redirect do /admin/login.
     * Używane jako middleware w Routerze.
     */
    public function requireAdmin(): void
    {
        if (!$this->isLoggedIn()) {
            redirect(url('/admin/login'));
        }
    }

    private function setCookie(string $token, int $hours): void
    {
        setcookie(self::COOKIE_NAME, $token, [
            'expires'  => time() + $hours * 3600,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $this->cookieSecure(),
            'samesite' => 'Lax',
        ]);
    }

    private function cookieSecure(): bool
    {
        // Secure only on HTTPS - automatycznie wykrywany
        return ($_SERVER['HTTPS'] ?? '') === 'on'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    private function renderMagicLinkEmail(string $name, string $magicLink, int $minutes): string
    {
        $name      = e($name);
        $magicLink = e($magicLink);
        return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#FFF8F0;font-family:Inter,Arial,sans-serif;color:#1A1A2E;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF8F0;padding:40px 20px;">
<tr><td align="center">
  <table width="560" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border-radius:16px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.05);">
    <tr><td style="background:linear-gradient(135deg,#FF6B35,#E55A2B);padding:32px;color:#FFFFFF;text-align:center;">
      <div style="font-size:32px;line-height:1;">☀️</div>
      <h1 style="margin:8px 0 0;font-family:Georgia,serif;font-size:24px;">Wyjazdownik.pl</h1>
    </td></tr>
    <tr><td style="padding:32px;">
      <p style="margin:0 0 16px;font-size:16px;">Cześć <strong>{$name}</strong>,</p>
      <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#374151;">
        Kliknij poniżej żeby się zalogować do panelu Wyjazdownika. Link wygaśnie za <strong>{$minutes} minut</strong>
        i zadziała tylko raz.
      </p>
      <p style="margin:0 0 24px;text-align:center;">
        <a href="{$magicLink}" style="display:inline-block;background:#FF6B35;color:#FFFFFF;text-decoration:none;padding:14px 28px;border-radius:999px;font-weight:600;font-size:16px;">
          Zaloguj się
        </a>
      </p>
      <p style="margin:0 0 8px;font-size:13px;color:#6B7280;">Jeśli przycisk nie działa, skopiuj ten adres do przeglądarki:</p>
      <p style="margin:0;word-break:break-all;font-size:12px;color:#6B7280;font-family:monospace;">{$magicLink}</p>
      <hr style="border:none;border-top:1px solid #E5E7EB;margin:32px 0;">
      <p style="margin:0;font-size:13px;color:#9CA3AF;">
        Nie próbowałeś się logować? Zignoruj tę wiadomość - bez kliknięcia w link nikt nie dostanie się do twojego konta.
      </p>
    </td></tr>
  </table>
  <p style="margin:16px 0 0;font-size:12px;color:#9CA3AF;">Wyjazdownik.pl - ogarnij wakacje ze znajomymi</p>
</td></tr>
</table>
</body>
</html>
HTML;
    }
}
