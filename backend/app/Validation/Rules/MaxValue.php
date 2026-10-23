<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;
use InvalidArgumentException;

/**
 * Numeric value lower than or equal to a limit (e.g. DECIMAL(15,2) capacity).
 */
final class MaxValue implements Rule
{
    /** @var numeric-string */
    private readonly string $max;

    public function __construct(string $max)
    {
        if (!is_numeric($max)) {
            throw new InvalidArgumentException("Rule limit must be numeric, [{$max}] given.");
        }
        $this->max = $max;
    }

    public function validate(mixed $value, array $data): ?string
    {
        if (is_numeric($value) && bccomp((string) $value, $this->max, 2) <= 0) {
            return null;
        }

        return "El campo :attribute no debe ser mayor a {$this->max}.";
    }
}
