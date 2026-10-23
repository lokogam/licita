<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;
use Closure;

/**
 * The value must not be taken yet according to a lookup (usually a database query).
 */
final class Unique implements Rule
{
    /**
     * @param Closure(mixed): bool $taken
     */
    public function __construct(
        private readonly Closure $taken,
        private readonly string $message = 'El valor de :attribute ya está en uso.',
    ) {
    }

    public function validate(mixed $value, array $data): ?string
    {
        return ($this->taken)($value) ? $this->message : null;
    }
}
