<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;

/**
 * The value must be a string.
 */
final class StringType implements Rule
{
    public function validate(mixed $value, array $data): ?string
    {
        return is_string($value) ? null : 'El campo :attribute debe ser texto.';
    }
}
