<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Routing;

use App\Core\Container;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\Middleware;
use App\Core\Routing\Router;
use Closure;
use PHPUnit\Framework\TestCase;

final class MiddlewareTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        RecordingMiddleware::$calls = [];
        $this->router = new Router(new Container());
        $this->router->aliasMiddleware('record', RecordingMiddleware::class);
        $this->router->aliasMiddleware('block', BlockingMiddleware::class);
    }

    public function test_group_middleware_wraps_the_handler_in_order(): void
    {
        $this->router->group('/api', function (Router $router): void {
            $router->get('/ping', function (Request $request): JsonResponse {
                RecordingMiddleware::$calls[] = 'handler:' . $request->attribute('trace');

                return new JsonResponse(['ok' => true]);
            });
        }, ['record:first', 'record:second']);

        $this->router->dispatch(Request::create('GET', '/api/ping'));

        $this->assertSame(['before:first', 'before:second', 'handler:first>second', 'after:second', 'after:first'], RecordingMiddleware::$calls);
    }

    public function test_middleware_can_short_circuit(): void
    {
        $this->router->group('/api', function (Router $router): void {
            $router->get('/secret', fn (): JsonResponse => new JsonResponse(['secret' => true]));
        }, ['block']);

        $response = $this->router->dispatch(Request::create('GET', '/api/secret'));

        $this->assertSame(403, $response->status());
    }

    public function test_nested_groups_accumulate_middleware_and_outer_routes_stay_untouched(): void
    {
        $this->router->group('/api', function (Router $router): void {
            $router->get('/public', fn (): JsonResponse => new JsonResponse(['public' => true]));
            $router->group('/admin', function (Router $router): void {
                $router->get('/panel', fn (): JsonResponse => new JsonResponse(['panel' => true]));
            }, ['block']);
        }, ['record:outer']);

        $this->assertSame(200, $this->router->dispatch(Request::create('GET', '/api/public'))->status());
        $this->assertSame(403, $this->router->dispatch(Request::create('GET', '/api/admin/panel'))->status());
        $this->assertSame(['before:outer', 'after:outer', 'before:outer', 'after:outer'], RecordingMiddleware::$calls);
    }
}

final class RecordingMiddleware implements Middleware
{
    /** @var list<string> */
    public static array $calls = [];

    public function handle(Request $request, Closure $next, string ...$parameters): Response
    {
        $name = $parameters[0] ?? '';
        self::$calls[] = "before:{$name}";
        $previous = $request->attribute('trace');
        $response = $next($request->withAttribute('trace', $previous === null ? $name : "{$previous}>{$name}"));
        self::$calls[] = "after:{$name}";

        return $response;
    }
}

final class BlockingMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, string ...$parameters): Response
    {
        return new JsonResponse(['message' => 'blocked'], 403);
    }
}
