<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;

/**
 * Positive decimal number using "." as separator and limited decimal places.
 */
final class Decimal implements Rule
{
    public function __construct(private readonly int $places = 2)
    {
    }

    public function validate(mixed $value, array $data): ?string
    {
        $string = is_int($value) || is_float($value) ? (string) $value : $value;

        if (is_string($string) && preg_match('/^\d+(\.\d{1,' . $this->places . '})?$/', $string) === 1) {
            return null;
        }

        return "El campo :attribute debe ser un número con máximo {$this->places} decimales (solo números y punto).";
    }
}
