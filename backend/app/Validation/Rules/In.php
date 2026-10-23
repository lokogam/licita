<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;

/**
 * The value must be one of a fixed list (strict, case sensitive).
 */
final class In implements Rule
{
    /**
     * @param list<string> $allowed
     */
    public function __construct(private readonly array $allowed)
    {
    }

    public function validate(mixed $value, array $data): ?string
    {
        if (is_string($value) && in_array($value, $this->allowed, true)) {
            return null;
        }

        return 'El campo :attribute debe ser uno de: ' . implode(', ', $this->allowed) . '.';
    }
}
