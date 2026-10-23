<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Core\Http\Request;
use App\Core\Kernel;
use App\Core\Routing\Route;
use App\Core\Routing\Router;
use PHPUnit\Framework\TestCase;

final class OpenApiDocumentationTest extends TestCase
{
    private Kernel $kernel;

    private Router $router;

    protected function setUp(): void
    {
        $this->kernel = require __DIR__ . '/../../../bootstrap/app.php';
        $this->router = $router; // defined by bootstrap/app.php
    }

    public function test_it_serves_a_valid_openapi_document(): void
    {
        $response = $this->kernel->handle(Request::create('GET', '/api/openapi.json'));

        $this->assertSame(200, $response->status());
        $spec = json_decode($response->content(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertStringStartsWith('3.', $spec['openapi']);
        $this->assertSame('API Módulo de Licitaciones', $spec['info']['title']);
        $this->assertArrayHasKey('Tender', $spec['components']['schemas']);
        $this->assertArrayHasKey('ValidationError', $spec['components']['schemas']);
    }

    public function test_every_registered_route_is_documented(): void
    {
        $spec = json_decode(
            $this->kernel->handle(Request::create('GET', '/api/openapi.json'))->content(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach ($this->router->routes() as $route) {
            if (in_array($route->uri, ['/api/openapi.json', '/api/docs'], true)) {
                continue;
            }

            $path = $this->openApiPath($route);
            $this->assertArrayHasKey($path, $spec['paths'], "Route {$route->uri} is not documented.");
            $this->assertArrayHasKey(
                strtolower($route->method),
                $spec['paths'][$path],
                "{$route->method} {$route->uri} is not documented.",
            );
        }
    }

    public function test_it_sends_cache_validators(): void
    {
        $response = $this->kernel->handle(Request::create('GET', '/api/openapi.json'));

        $this->assertMatchesRegularExpression('/^"[a-f0-9]{40}"$/', $response->headers()['ETag']);
        $this->assertSame('public, max-age=3600', $response->headers()['Cache-Control']);
    }

    public function test_it_answers_304_when_the_document_did_not_change(): void
    {
        $etag = $this->kernel->handle(Request::create('GET', '/api/openapi.json'))->headers()['ETag'];

        $response = $this->kernel->handle(
            Request::create('GET', '/api/openapi.json', headers: ['If-None-Match' => $etag]),
        );

        $this->assertSame(304, $response->status());
        $this->assertSame('', $response->content());
    }

    public function test_it_serves_the_swagger_ui(): void
    {
        $response = $this->kernel->handle(Request::create('GET', '/api/docs'));

        $this->assertSame(200, $response->status());
        $this->assertStringStartsWith('text/html', $response->headers()['Content-Type']);
        $this->assertStringContainsString('swagger-ui', $response->content());
        $this->assertStringContainsString('/api/openapi.json', $response->content());
    }

    /**
     * "/api/tenders/{id:\d+}" -> "/api/tenders/{id}"
     */
    private function openApiPath(Route $route): string
    {
        return (string) preg_replace('/\{(\w+):[^}]+\}/', '{$1}', $route->uri);
    }
}
