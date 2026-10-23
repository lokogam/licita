<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;

/**
 * The value must be a syntactically valid email address.
 */
final class Email implements Rule
{
    public function validate(mixed $value, array $data): ?string
    {
        if (is_string($value) && filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false) {
            return null;
        }

        return 'El campo :attribute debe ser un correo electrónico válido.';
    }
}
