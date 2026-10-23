<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Exceptions\HttpException;

/**
 * 401: missing, invalid or expired credentials.
 */
final class AuthenticationException extends HttpException
{
    public function __construct(string $message = 'La sesión no es válida o expiró. Inicie sesión nuevamente.')
    {
        parent::__construct(401, $message, ['WWW-Authenticate' => 'Bearer']);
    }
}
