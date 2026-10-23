<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Core\Http\UploadedFile;
use App\Validation\Rule;

/**
 * Maximum upload size in kilobytes.
 */
final class MaxKilobytes implements Rule
{
    public function __construct(private readonly int $kilobytes)
    {
    }

    public function validate(mixed $value, array $data): ?string
    {
        if ($value instanceof UploadedFile && $value->size() <= $this->kilobytes * 1024) {
            return null;
        }

        $readable = $this->kilobytes >= 1024
            ? round($this->kilobytes / 1024, 1) . ' MB'
            : $this->kilobytes . ' KB';

        return "El archivo :attribute no debe superar {$readable}.";
    }
}
