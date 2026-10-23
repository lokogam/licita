<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\HttpException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\Router;
use Throwable;

/**
 * HTTP kernel: receives a Request and always produces a Response.
 */
final class Kernel
{
    public function __construct(
        private readonly Router $router,
        private readonly ExceptionHandler $exceptionHandler,
        private readonly string $allowedOrigin = '*',
        private readonly bool $trustProxy = false,
    ) {
    }

    /**
     * Whether the app runs behind a proxy whose X-Forwarded-For can be trusted.
     */
    public function trustsProxy(): bool
    {
        return $this->trustProxy;
    }

    public function handle(Request $request): Response
    {
        if ($request->method() === 'OPTIONS') {
            return $this->withCors(new Response('', 204));
        }

        try {
            if ($request->hasMalformedBody()) {
                throw new HttpException(
                    400,
                    'El cuerpo de la petición no es un JSON válido (verifique que esté codificado en UTF-8).',
                );
            }

            $response = $this->router->dispatch($request);
        } catch (Throwable $e) {
            $response = $this->exceptionHandler->render($e);
        }

        return $this->withCors($response);
    }

    private function withCors(Response $response): Response
    {
        $headers = [
            'Access-Control-Allow-Origin' => $this->allowedOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization, X-Requested-With',
            'Access-Control-Expose-Headers' => 'Content-Disposition, Retry-After',
        ];

        // The refresh cookie only travels cross-origin to an explicit origin, never to "*".
        if ($this->allowedOrigin !== '*') {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        return $response->withHeaders($headers);
    }
}
