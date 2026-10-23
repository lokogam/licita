<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Core\Http\UploadedFile;
use App\Validation\Rule;

/**
 * Restricts uploads by extension AND by the MIME type detected from the content.
 */
final class Mimes implements Rule
{
    private const MIME_TYPES = [
        'pdf' => ['application/pdf'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/x-zip'],
    ];

    /**
     * @param list<string> $extensions
     */
    public function __construct(private readonly array $extensions)
    {
    }

    public function validate(mixed $value, array $data): ?string
    {
        if ($value instanceof UploadedFile && in_array($value->extension(), $this->extensions, true)) {
            $allowed = self::MIME_TYPES[$value->extension()] ?? [];
            if (in_array($value->detectedMimeType(), $allowed, true)) {
                return null;
            }
        }

        $list = strtoupper(implode(', ', $this->extensions));

        return "El archivo :attribute debe ser de tipo {$list}.";
    }
}
