<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

/**
 * 404: the requested route or resource does not exist.
 */
final class NotFoundHttpException extends HttpException
{
    public function __construct(string $message = 'Recurso no encontrado.')
    {
        parent::__construct(404, $message);
    }
}
