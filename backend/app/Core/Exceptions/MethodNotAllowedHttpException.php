<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

/**
 * 405: the route exists but not for the requested HTTP method.
 */
final class MethodNotAllowedHttpException extends HttpException
{
    /**
     * @param list<string> $allowedMethods
     */
    public function __construct(array $allowedMethods)
    {
        parent::__construct(405, 'Método no permitido.', ['Allow' => implode(', ', $allowedMethods)]);
    }
}
