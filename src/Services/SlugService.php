<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Trip;

/**
 * Generowanie slugów z polskich nazw.
 *
 * "Lato 2026 z ekipą" → "lato-2026-z-ekipa"
 * Sprawdza unikalność w `trips` - jeśli zajęte, dodaje sufiks "-2", "-3" itd.
 */
final class SlugService
{
    private const POLISH_MAP = [
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
        'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        'Ą' => 'a', 'Ć' => 'c', 'Ę' => 'e', 'Ł' => 'l', 'Ń' => 'n',
        'Ó' => 'o', 'Ś' => 's', 'Ź' => 'z', 'Ż' => 'z',
    ];

    public static function slugify(string $text): string
    {
        $text = strtr($text, self::POLISH_MAP);
        $text = mb_strtolower($text, 'UTF-8');
        $text = (string) preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim($text, '-');
        return $text === '' ? 'wyjazd' : $text;
    }

    /**
     * Generuje slug który nie jest zajęty w bazie.
     * Jeśli już istnieje, dorzuca "-2", "-3" itd.
     */
    public static function unique(string $text, ?int $exceptId = null): string
    {
        $base = self::slugify($text);
        $slug = $base;
        $i    = 2;
        while (Trip::slugExists($slug, $exceptId)) {
            $slug = $base . '-' . $i;
            $i++;
            if ($i > 999) {
                $slug = $base . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
                break;
            }
        }
        return $slug;
    }
}
