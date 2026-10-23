<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;

/**
 * Array with at least N items.
 */
final class MinItems implements Rule
{
    public function __construct(private readonly int $min)
    {
    }

    public function validate(mixed $value, array $data): ?string
    {
        if (is_array($value) && count($value) >= $this->min) {
            return null;
        }

        return "El campo :attribute debe tener al menos {$this->min} elemento(s).";
    }
}
