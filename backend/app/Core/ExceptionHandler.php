<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\HttpException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\JsonResponse;
use Throwable;

/**
 * Converts any exception into a consistent JSON error response.
 */
final class ExceptionHandler
{
    public function __construct(
        private readonly bool $debug = false,
        private readonly ?string $logFile = null,
    ) {
    }

    public function render(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return new JsonResponse(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        if ($e instanceof HttpException) {
            return new JsonResponse(['message' => $e->getMessage()], $e->statusCode(), $e->headers());
        }

        $this->report($e);

        $payload = ['message' => 'Ocurrió un error inesperado. Intente nuevamente.'];
        if ($this->debug) {
            $payload['exception'] = $e::class;
            $payload['detail'] = $e->getMessage();
            $payload['trace'] = array_slice(explode("\n", $e->getTraceAsString()), 0, 15);
        }

        return new JsonResponse($payload, 500);
    }

    /**
     * Writes unexpected errors to the application log.
     */
    private function report(Throwable $e): void
    {
        if ($this->logFile === null) {
            return;
        }

        $line = sprintf(
            "[%s] %s: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
        );

        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
