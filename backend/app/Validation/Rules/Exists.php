<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;
use Closure;

/**
 * The value must exist according to a lookup (usually a database query).
 */
final class Exists implements Rule
{
    /**
     * @param Closure(mixed): bool $lookup
     */
    public function __construct(
        private readonly Closure $lookup,
        private readonly string $message = 'El valor seleccionado en :attribute no existe.',
    ) {
    }

    public function validate(mixed $value, array $data): ?string
    {
        return ($this->lookup)($value) ? null : $this->message;
    }
}
