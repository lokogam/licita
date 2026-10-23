<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Container;
use App\Core\ExceptionHandler;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Kernel;
use App\Core\Routing\Router;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class KernelTest extends TestCase
{
    public function test_it_returns_the_route_response(): void
    {
        $kernel = $this->kernel(function (Router $router): void {
            $router->get('/api/ping', fn (): JsonResponse => new JsonResponse(['ok' => true]));
        });

        $response = $kernel->handle(Request::create('GET', '/api/ping'));

        $this->assertSame(200, $response->status());
    }

    public function test_it_renders_not_found_as_json(): void
    {
        $response = $this->kernel()->handle(Request::create('GET', '/api/missing'));

        $this->assertSame(404, $response->status());
        $this->assertSame('Recurso no encontrado.', $this->json($response)['message']);
    }

    public function test_it_renders_method_not_allowed_as_json(): void
    {
        $kernel = $this->kernel(function (Router $router): void {
            $router->get('/api/ping', fn (): JsonResponse => new JsonResponse([]));
        });

        $this->assertSame(405, $kernel->handle(Request::create('POST', '/api/ping'))->status());
    }

    public function test_it_renders_validation_errors_with_422(): void
    {
        $kernel = $this->kernel(function (Router $router): void {
            $router->post('/api/items', function (): never {
                throw new ValidationException(['subject' => ['El campo objeto es obligatorio.']]);
            });
        });

        $response = $kernel->handle(Request::create('POST', '/api/items'));

        $this->assertSame(422, $response->status());
        $this->assertSame(
            ['subject' => ['El campo objeto es obligatorio.']],
            $this->json($response)['errors'],
        );
    }

    public function test_it_hides_internal_errors_when_debug_is_off(): void
    {
        $kernel = $this->kernel(function (Router $router): void {
            $router->get('/api/boom', function (): never {
                throw new RuntimeException('SQLSTATE secret details');
            });
        });

        $response = $kernel->handle(Request::create('GET', '/api/boom'));

        $this->assertSame(500, $response->status());
        $this->assertStringNotContainsString('SQLSTATE', $response->content());
    }

    public function test_it_rejects_malformed_json_bodies_with_400(): void
    {
        $kernel = $this->kernel(function (Router $router): void {
            $router->post('/api/items', fn (): JsonResponse => new JsonResponse([], 201));
        });

        $response = $kernel->handle(Request::create(
            'POST',
            '/api/items',
            headers: ['Content-Type' => 'application/json'],
            rawBody: "{\"subject\": \"Compra de port\xE1tiles\"}", // Latin-1 byte, not UTF-8
        ));

        $this->assertSame(400, $response->status());
        $this->assertSame(
            'El cuerpo de la petición no es un JSON válido (verifique que esté codificado en UTF-8).',
            $this->json($response)['message'],
        );
    }

    public function test_it_answers_cors_preflight_requests(): void
    {
        $response = $this->kernel()->handle(Request::create('OPTIONS', '/api/tenders'));

        $this->assertSame(204, $response->status());
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $response->headers());
    }

    /**
     * @param (callable(Router): void)|null $routes
     */
    private function kernel(?callable $routes = null): Kernel
    {
        $container = new Container();
        $router = new Router($container);

        if ($routes !== null) {
            $routes($router);
        }

        return new Kernel($router, new ExceptionHandler(debug: false), allowedOrigin: '*');
    }

    /**
     * @return array<string, mixed>
     */
    private function json(\App\Core\Http\Response $response): array
    {
        return json_decode($response->content(), true, flags: JSON_THROW_ON_ERROR);
    }
}
