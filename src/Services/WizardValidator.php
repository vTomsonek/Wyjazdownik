<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\QuestionLabels;

/**
 * Walidacja wartosci pola wizarda - zgodnie z metadanymi z QuestionLabels.
 * Zwraca komunikat bledu (string) lub null gdy OK.
 */
final class WizardValidator
{
    public static function validate(string $key, mixed $value): ?string
    {
        $meta = QuestionLabels::get($key);
        if ($meta === null) return 'Nieznane pytanie.';

        // Trip expectations: max 3
        if ($key === 'trip_expectations' && is_array($value)) {
            $max = (int) ($meta['max_selections'] ?? 3);
            if (count($value) > $max) {
                return 'Wybierz maksymalnie ' . $max . ' opcji.';
            }
        }

        // Multi-choice: wartosci musza byc z opcji
        if (($meta['multi'] ?? false) === true && is_array($value)) {
            $allowed = array_keys($meta['options'] ?? []);
            foreach ($value as $v) {
                if (!in_array((string) $v, $allowed, true)) {
                    return 'Niepoprawna wartosc.';
                }
            }
        }

        // Single-choice
        if (!($meta['multi'] ?? false) && isset($meta['options']) && $value !== '' && $value !== null) {
            $allowed = array_keys($meta['options']);
            if (!in_array((string) $value, $allowed, true)) {
                return 'Niepoprawna wartosc.';
            }
        }

        // Slider
        if (($meta['type'] ?? '') === 'slider') {
            $min = (int) ($meta['min'] ?? 0);
            $max = (int) ($meta['max'] ?? 100);
            $iv = (int) $value;
            if ($iv < $min || $iv > $max) {
                return 'Wartosc poza zakresem.';
            }
        }

        // Languages: oczekujemy obiektu {lang: level}
        if ($key === 'languages' && is_array($value)) {
            $levels = array_keys($meta['levels'] ?? []);
            foreach ($value as $lvl) {
                if (!in_array((string) $lvl, $levels, true)) {
                    return 'Niepoprawny poziom jezyka.';
                }
            }
        }

        return null;
    }
}
