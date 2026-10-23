<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;

/**
 * The value must be an integer (digits only).
 */
final class IntegerType implements Rule
{
    public function validate(mixed $value, array $data): ?string
    {
        if (is_int($value) || (is_string($value) && preg_match('/^\d+$/', $value) === 1)) {
            return null;
        }

        return 'El campo :attribute debe ser un número entero.';
    }
}
