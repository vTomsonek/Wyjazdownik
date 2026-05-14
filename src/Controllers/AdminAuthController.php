<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Helpers\Csrf;
use App\Services\AuthService;
use App\Services\MailerService;
use App\Services\RateLimiter;

/**
 * Logowanie admina przez magic link.
 */
final class AdminAuthController extends Controller
{
    public function showLogin(Request $request): never
    {
        $auth = new AuthService();
        if ($auth->isLoggedIn()) {
            $this->redirect(url('/admin'));
        }

        $this->render('admin/login', [
            'title'         => 'Zaloguj się - Wyjazdownik.pl',
            'description'   => 'Logowanie administratora.',
            'flashSuccess'  => flash('success'),
            'flashError'    => flash('error'),
            'flashEmail'    => flash('email'),
            'devMagicLink'  => $this->popDevMagicLink(),
        ], layout: 'admin');
    }

    public function sendMagicLink(Request $request): never
    {
        Csrf::validate();

        $email = trim((string) $request->input('email', ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Wpisz poprawny adres email.');
            flash('email', $email);
            $this->redirect(url('/admin/login'));
        }

        $limiter = RateLimiter::forLogin($request->ip());
        if (!$limiter->hit()) {
            flash('error', 'Zbyt wiele prób logowania. Spróbuj ponownie za kilkanaście minut.');
            $this->redirect(url('/admin/login'));
        }

        try {
            (new AuthService())->sendMagicLink($email, MailerService::fromConfig());
        } catch (\Throwable $e) {
            // Loguj ale nie crashuj aplikacji - user dostaje przyjazny komunikat
            error_log('[wyjazdownik] Magic link send failed: ' . $e->getMessage());
            flash('error', 'Nie udało się wysłać maila. Sprawdź konfigurację SMTP lub skontaktuj się z administratorem.');
            flash('email', $email);
            $this->redirect(url('/admin/login'));
        }

        flash('success', 'Wysłaliśmy do ciebie link do zalogowania. Sprawdź skrzynkę.');
        $this->redirect(url('/admin/login'));
    }

    public function authenticate(Request $request): never
    {
        $token = (string) $request->input('token', '');
        $auth  = new AuthService();
        $admin = $auth->authenticateByToken($token, $request->ip(), $request->userAgent());

        if ($admin === null) {
            flash('error', 'Link wygasł lub jest nieprawidłowy. Wygeneruj nowy.');
            $this->redirect(url('/admin/login'));
        }

        // Reset rate limitera po udanym logowaniu
        RateLimiter::forLogin($request->ip())->reset();

        flash('success', 'Cześć ' . $admin->name . '! Zalogowano pomyślnie.');
        $this->redirect(url('/admin'));
    }

    public function logout(Request $request): never
    {
        (new AuthService())->logout();
        flash('success', 'Wylogowano. Do zobaczenia!');
        $this->redirect(url('/'));
    }

    /**
     * Pobiera ostatni magic link z sesji (zapisany przez MailerService gdy MAIL_DRIVER=log)
     * i czyści go - żeby nie wisiał wiecznie po jednym wyświetleniu.
     */
    private function popDevMagicLink(): ?string
    {
        if ((string) config('app.env') !== 'dev') {
            return null;
        }
        $link = $_SESSION['_last_magic_link'] ?? null;
        unset($_SESSION['_last_magic_link']);
        return is_string($link) ? $link : null;
    }
}
