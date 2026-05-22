<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Bezpieczny upload plików (banery wyjazdów, avatary uczestników).
 *
 * Sprawdzenia:
 *   - $_FILES upload errors (size, partial, etc.)
 *   - finfo MIME type (NIE rozszerzenie - bo można podrobic)
 *   - max size z config('upload.max_size')
 *   - whitelist MIME z config('upload.allowed_mimes')
 *   - losowa nazwa: bin2hex(random_bytes(16)) + ext
 *
 * Zapisuje do public/assets/uploads/{banners|avatars}/.
 * Zwraca względną ścieżkę (od /public/) - do zapisania w DB.
 */
final class UploadService
{
    /**
     * @param array<string,mixed> $file Wpis z $_FILES['fieldname']
     * @return string|null Względna ścieżka (np. "assets/uploads/banners/abc123.jpg") lub null gdy nic nie wgrano.
     * @throws RuntimeException przy błędzie walidacji
     */
    public static function uploadBanner(array $file): ?string
    {
        return self::handle($file, (string) config('upload.banner_dir', 'banners'));
    }

    /**
     * @param array<string,mixed> $file
     */
    public static function uploadAvatar(array $file): ?string
    {
        return self::handle($file, (string) config('upload.avatar_dir', 'avatars'));
    }

    /**
     * Upload zdjecia dla miejsca atrakcji. Max 5MB, JPG/PNG/WebP.
     * Pliki idą do public/assets/uploads/places/{place_id}/.
     * @param array<string,mixed> $file
     */
    public static function uploadPlaceImage(array $file, int $placeId): ?string
    {
        return self::handle(
            $file,
            'places/' . $placeId,
            5 * 1024 * 1024,  // 5 MB
            ['image/jpeg', 'image/png', 'image/webp']
        );
    }

    /**
     * Upload wideo dla miejsca atrakcji. Max 50MB, MP4/WebM/QuickTime.
     * @param array<string,mixed> $file
     */
    public static function uploadPlaceVideo(array $file, int $placeId): ?string
    {
        return self::handle(
            $file,
            'places/' . $placeId,
            50 * 1024 * 1024,  // 50 MB
            ['video/mp4', 'video/webm', 'video/quicktime']
        );
    }

    /**
     * Usuwa plik z dysku. Bezpieczne - sprawdza że ścieżka jest w obrębie uploads/.
     */
    public static function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        $path = realpath(BASE_PATH . '/public/' . ltrim($relativePath, '/'));
        $uploadsDir = realpath(BASE_PATH . '/public/assets/uploads');
        if ($path === false || $uploadsDir === false) {
            return;
        }
        if (!str_starts_with($path, $uploadsDir)) {
            return; // Poza uploads dir - nie kasujemy
        }
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * @param array<string,mixed> $file
     * @param list<string>|null   $allowedMimes Override domyslnych mime types
     * @param int|null            $maxSize      Override domyslnego limitu rozmiaru (bajty)
     */
    private static function handle(
        array $file,
        string $subdir,
        ?int $maxSize = null,
        ?array $allowedMimes = null
    ): ?string {
        // Brak uploadu - to OK, wracamy null
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Błąd uploadu: kod ' . $error);
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size    = (int)    ($file['size']     ?? 0);
        if (!is_uploaded_file($tmpName)) {
            throw new RuntimeException('To nie jest legalny upload.');
        }

        $maxSize = $maxSize ?? (int) config('upload.max_size', 2_097_152);
        if ($size > $maxSize) {
            throw new RuntimeException(
                'Plik za duży (max ' . round($maxSize / 1024 / 1024, 1) . ' MB).'
            );
        }

        // MIME przez finfo - nie ufamy $_FILES['type']
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($tmpName);
        $allowed = $allowedMimes ?? (array) config('upload.allowed_mimes', ['image/jpeg', 'image/png', 'image/webp']);
        if (!in_array($mime, $allowed, true)) {
            throw new RuntimeException(
                'Nieobsługiwany typ pliku (' . $mime . '). Dozwolone: ' . implode(', ', $allowed)
            );
        }

        $ext = match ($mime) {
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'video/mp4'       => 'mp4',
            'video/webm'      => 'webm',
            'video/quicktime' => 'mov',
            default           => 'bin',
        };

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $relDir   = 'assets/uploads/' . $subdir;
        $absDir   = BASE_PATH . '/public/' . $relDir;
        if (!is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }
        $absPath = $absDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $absPath)) {
            throw new RuntimeException('Nie udało się zapisać pliku.');
        }

        return $relDir . '/' . $filename;
    }
}
