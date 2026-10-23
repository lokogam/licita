<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * A single validation constraint.
 */
interface Rule
{
    /**
     * Returns an error message (":attribute" is replaced by the field label) or null when valid.
     *
     * @param array<string, mixed> $data Whole payload, for rules that compare fields.
     */
    public function validate(mixed $value, array $data): ?string;
}
