<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\Rule;
use App\Core\Http\UploadedFile;

/**
 * The field must be present and not empty or whitespace only.
 */
final class Required implements Rule
{
    public function validate(mixed $value, array $data): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return 'El campo :attribute es obligatorio.';
        }

        if ($value instanceof UploadedFile && $value->path() === '') {
            return 'El campo :attribute es obligatorio.';
        }

        if (is_string($value) && trim($value) === '') {
            return 'El campo :attribute no puede contener solo espacios.';
        }

        return null;
    }
}
