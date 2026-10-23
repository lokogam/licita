<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;

/**
 * Minimum number of characters (multibyte aware).
 */
final class Min implements Rule
{
    public function __construct(private readonly int $min)
    {
    }

    public function validate(mixed $value, array $data): ?string
    {
        if (!is_string($value) || mb_strlen($value) >= $this->min) {
            return null;
        }

        return "El campo :attribute debe tener al menos {$this->min} caracteres.";
    }
}
