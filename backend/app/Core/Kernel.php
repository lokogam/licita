<?php

declare(strict_types=1);

namespace App\Core;

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
    ) {
    }

    public function handle(Request $request): Response
    {
        if ($request->method() === 'OPTIONS') {
            return $this->withCors(new Response('', 204));
        }

        try {
            $response = $this->router->dispatch($request);
        } catch (Throwable $e) {
            $response = $this->exceptionHandler->render($e);
        }

        return $this->withCors($response);
    }

    private function withCors(Response $response): Response
    {
        return $response->withHeaders([
            'Access-Control-Allow-Origin' => $this->allowedOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, X-Requested-With',
            'Access-Control-Expose-Headers' => 'Content-Disposition',
        ]);
    }
}
