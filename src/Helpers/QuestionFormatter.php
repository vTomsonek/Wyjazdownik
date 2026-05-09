<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Formatuje wartosc odpowiedzi (z DB) na czytelna polska etykiete.
 * Wynesione z QuestionLabels zeby tamten plik byl mniejszy.
 */
final class QuestionFormatter
{
    public static function format(string $key, mixed $value): string
    {
        $meta = QuestionLabels::get($key);
        if ($meta === null) {
            return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        // Languages: filtruj 'none', mapuj nazwy + poziomy na PL
        if ($key === 'languages' && is_array($value)) {
            $names  = $meta['languages'] ?? [];
            $levels = $meta['levels']    ?? [];
            $parts  = [];
            foreach ($value as $lang => $level) {
                if ($level === 'none' || $level === '' || $level === null) continue;
                $name = $names[(string) $lang] ?? (string) $lang;
                $lvl  = $levels[(string) $level] ?? (string) $level;
                $parts[] = $name . ' (' . mb_strtolower($lvl) . ')';
            }
            return empty($parts) ? '— brak —' : implode(', ', $parts);
        }

        // Multi-choice
        if (($meta['multi'] ?? false) === true && is_array($value)) {
            $opts = $meta['options'] ?? [];
            $out  = [];
            foreach ($value as $v) {
                $out[] = $opts[(string) $v] ?? (string) $v;
            }
            return implode(', ', $out);
        }

        // Slider z jednostka
        if (($meta['type'] ?? '') === 'slider') {
            $unit = (string) ($meta['unit'] ?? '');
            $val  = is_numeric($value) ? number_format((int) $value, 0, ',', ' ') : (string) $value;
            return $unit !== '' ? $val . ' ' . $unit : $val;
        }

        // Single-choice z opcjami
        if (isset($meta['options']) && is_string($value)) {
            return $meta['options'][$value] ?? $value;
        }

        // Boolean fallback
        if (is_bool($value)) {
            return $value ? 'Tak' : 'Nie';
        }

        // Asoc array fallback
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $k => $v) {
                $parts[] = $k . ': ' . (is_scalar($v) ? (string) $v : json_encode($v));
            }
            return implode(', ', $parts);
        }

        return (string) $value;
    }
}
