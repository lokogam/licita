<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Core\Exceptions\HttpException;

/**
 * The antivirus could not be reached or failed; uploads are refused (fail closed) with 503.
 */
final class ScannerUnavailableException extends HttpException
{
    public function __construct(string $reason = '')
    {
        parent::__construct(
            503,
            'El servicio antivirus no está disponible. Intente nuevamente en unos minutos.',
            ['Retry-After' => '60'],
            $reason !== '' ? new \RuntimeException($reason) : null,
        );
    }
}
