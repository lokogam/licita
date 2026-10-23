<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Core\Http\UploadedFile;
use App\Validation\Rule;

/**
 * The value must be a successfully uploaded file.
 */
final class File implements Rule
{
    public function validate(mixed $value, array $data): ?string
    {
        if ($value instanceof UploadedFile && $value->isValid()) {
            return null;
        }

        return 'El campo :attribute debe ser un archivo cargado correctamente.';
    }
}
