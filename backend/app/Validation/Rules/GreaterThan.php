<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;
use InvalidArgumentException;

/**
 * Numeric value strictly greater than a threshold.
 */
final class GreaterThan implements Rule
{
    /** @var numeric-string */
    private readonly string $threshold;

    public function __construct(string $threshold)
    {
        if (!is_numeric($threshold)) {
            throw new InvalidArgumentException("Rule limit must be numeric, [{$threshold}] given.");
        }
        $this->threshold = $threshold;
    }

    public function validate(mixed $value, array $data): ?string
    {
        if (is_numeric($value) && bccomp((string) $value, $this->threshold, 2) === 1) {
            return null;
        }

        return "El campo :attribute debe ser mayor a {$this->threshold}.";
    }
}
