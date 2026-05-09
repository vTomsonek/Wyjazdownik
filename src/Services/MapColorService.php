<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Deterministyczne przypisywanie koloru uczestnikowi - na podstawie access_token.
 * Ten sam uczestnik zawsze dostaje ten sam kolor (po reload, w podsumowaniu, etc).
 *
 * 12 odroznialnych kolorow, dobrane zeby kontrastowaly i pasowaly do brandu.
 */
final class MapColorService
{
    private const PALETTE = [
        '#FF6B35', // brand orange
        '#2EC4B6', // brand turquoise
        '#FFD23F', // brand yellow
        '#10B981', // emerald
        '#3B82F6', // blue
        '#8B5CF6', // violet
        '#EC4899', // pink
        '#EF4444', // red
        '#84CC16', // lime
        '#F97316', // amber
        '#06B6D4', // cyan
        '#A855F7', // purple
    ];

    public static function forToken(string $accessToken): string
    {
        $hash  = md5($accessToken);
        $index = hexdec(substr($hash, 0, 6)) % count(self::PALETTE);
        return self::PALETTE[$index];
    }

    /**
     * @return list<string>
     */
    public static function palette(): array
    {
        return self::PALETTE;
    }
}
