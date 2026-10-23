<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;

/**
 * Maximum number of characters (multibyte aware).
 */
final class Max implements Rule
{
    public function __construct(private readonly int $max)
    {
    }

    public function validate(mixed $value, array $data): ?string
    {
        if (!is_string($value) || mb_strlen($value) <= $this->max) {
            return null;
        }

        return "El campo :attribute no debe superar {$this->max} caracteres.";
    }
}
