<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Core\Http\UploadedFile;
use App\Services\Security\VirusScanner;
use App\Validation\Rule;

/**
 * The uploaded file must pass the antivirus scan.
 * If the antivirus is unavailable the ScannerUnavailableException (503) propagates: fail closed.
 */
final class Clean implements Rule
{
    public function __construct(private readonly VirusScanner $scanner)
    {
    }

    public function validate(mixed $value, array $data): ?string
    {
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            return null;
        }

        return $this->scanner->scan($value->path())->clean
            ? null
            : 'El archivo :attribute fue rechazado por el antivirus.';
    }
}
