<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Exceptions\HttpException;

/**
 * 429: login temporarily locked after repeated failures.
 */
final class TooManyAttemptsException extends HttpException
{
    public function __construct(int $minutes)
    {
        parent::__construct(
            429,
            "Demasiados intentos fallidos. Intente de nuevo en {$minutes} minutos.",
            ['Retry-After' => (string) ($minutes * 60)],
        );
    }
}
