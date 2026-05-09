<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Prosty validator - akumuluje błędy w tablicy $errors[fieldName] = "komunikat".
 * Po walidacji wszystkich pól, isValid() mówi czy można zapisać do DB.
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    /** @var array<string,mixed> */
    private array $data;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $message = 'To pole jest wymagane.'): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->errors[$field] ??= $message;
        }
        return $this;
    }

    public function maxLength(string $field, int $max, ?string $message = null): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) > $max) {
            $this->errors[$field] ??= $message ?? "Maksymalna długość: {$max} znaków.";
        }
        return $this;
    }

    public function date(string $field, string $message = 'Niepoprawna data.'): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($value === '') return $this;
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            $this->errors[$field] ??= $message;
        }
        return $this;
    }

    public function dateAfter(string $field, string $otherField, string $message = 'Data musi być po dacie początkowej.'): self
    {
        $a = (string) ($this->data[$otherField] ?? '');
        $b = (string) ($this->data[$field] ?? '');
        if ($a === '' || $b === '') return $this;
        if (strtotime($b) < strtotime($a)) {
            $this->errors[$field] ??= $message;
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $message = 'Niepoprawna wartość.'): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && !in_array((string) $value, $allowed, true)) {
            $this->errors[$field] ??= $message;
        }
        return $this;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * @return array<string,string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;
        return $this;
    }
}
