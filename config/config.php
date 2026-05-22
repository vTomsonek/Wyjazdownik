<?php
declare(strict_types=1);

/**
 * Application configuration.
 *
 * Read via the config() helper, e.g. config('database.host').
 * Source of truth is .env for environment-specific values.
 */

return [

    'app' => [
        'env'      => env('APP_ENV', 'prod'),
        'name'     => env('APP_NAME', 'Wyjazdownik'),
        'url'      => env('APP_URL', 'http://localhost'),
        'timezone' => env('APP_TIMEZONE', 'Europe/Warsaw'),
        'debug'    => env('APP_ENV', 'prod') === 'dev',
        // Wersja projektu - aktualizuj przy kazdym wiekszym release.
        'version'  => '1.4.2',
    ],

    'database' => [
        'host'    => env('DB_HOST', '127.0.0.1'),
        'port'    => (int) env('DB_PORT', 3306),
        'name'    => env('DB_NAME', 'wyjazdownik'),
        'user'    => env('DB_USER', 'root'),
        'pass'    => env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        'driver'       => env('MAIL_DRIVER', 'log'),
        'host'         => env('MAIL_HOST', 'localhost'),
        'port'         => (int) env('MAIL_PORT', 25),
        'username'     => env('MAIL_USERNAME', ''),
        'password'     => env('MAIL_PASSWORD', ''),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@wyjazdownik.pl'),
        'from_name'    => env('MAIL_FROM_NAME', 'Wyjazdownik'),
    ],

    'admin' => [
        'initial_email' => env('ADMIN_INITIAL_EMAIL', 'admin@example.com'),
        'initial_name'  => env('ADMIN_INITIAL_NAME', 'Admin'),
    ],

    'security' => [
        'session_lifetime_hours'      => (int) env('SESSION_LIFETIME_HOURS', 24),
        'magic_link_lifetime_minutes' => (int) env('MAGIC_LINK_LIFETIME_MINUTES', 15),
        'login_max_attempts'          => 3,
        'login_window_minutes'        => 15,
        'submit_max_attempts'         => 30,
        'submit_window_minutes'       => 60,
    ],

    'upload' => [
        'max_size'      => (int) env('UPLOAD_MAX_SIZE', 2_097_152),
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'banner_dir'    => 'banners',
        'avatar_dir'    => 'avatars',
    ],

    // Linki do social mediow - puste pole = ikonka sie nie pokazuje.
    'social' => [
        'facebook'  => trim((string) env('SOCIAL_FACEBOOK', '')),
        'instagram' => trim((string) env('SOCIAL_INSTAGRAM', '')),
        'tiktok'    => trim((string) env('SOCIAL_TIKTOK', '')),
    ],

    // Google Maps - klucz API potrzebny do mapy atrakcji (Etap 1 nowej funkcji).
    // Restrict klucz po HTTP referrer dla wyjazdownik.pl/*.
    'google' => [
        'maps_api_key' => trim((string) env('GOOGLE_MAPS_API_KEY', '')),
    ],

];
