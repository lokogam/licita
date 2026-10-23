<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;

/**
 * The value must be an array.
 */
final class ArrayType implements Rule
{
    public function validate(mixed $value, array $data): ?string
    {
        return is_array($value) ? null : 'El campo :attribute debe ser una lista.';
    }
}
