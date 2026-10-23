<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Routing;

use App\Core\Container;
use App\Core\Exceptions\MethodNotAllowedHttpException;
use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router(new Container());
    }

    public function test_it_dispatches_to_a_closure_handler(): void
    {
        $this->router->get('/api/ping', fn (): JsonResponse => new JsonResponse(['pong' => true]));

        $response = $this->router->dispatch(Request::create('GET', '/api/ping'));

        $this->assertSame(['pong' => true], $response->data());
    }

    public function test_it_dispatches_to_a_controller_action_with_route_params(): void
    {
        $this->router->get('/api/items/{id}', [FakeController::class, 'show']);

        $response = $this->router->dispatch(Request::create('GET', '/api/items/42'));

        $this->assertSame(['id' => '42'], $response->data());
    }

    public function test_it_applies_group_prefixes(): void
    {
        $this->router->group('/api', function (Router $router): void {
            $router->post('/items', [FakeController::class, 'store']);
        });

        $response = $this->router->dispatch(Request::create('POST', '/api/items'));

        $this->assertSame(201, $response->status());
    }

    public function test_numeric_params_reject_non_numeric_values(): void
    {
        $this->router->get('/api/items/{id:\d+}', [FakeController::class, 'show']);

        $this->expectException(NotFoundHttpException::class);

        $this->router->dispatch(Request::create('GET', '/api/items/abc'));
    }

    public function test_static_routes_win_over_parameterized_ones_registered_earlier(): void
    {
        $this->router->get('/api/items/{id}', [FakeController::class, 'show']);
        $this->router->get('/api/items/export', fn (): JsonResponse => new JsonResponse(['export' => true]));

        $response = $this->router->dispatch(Request::create('GET', '/api/items/export'));

        $this->assertSame(['export' => true], $response->data());
    }

    public function test_it_throws_not_found_for_unknown_paths(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->router->dispatch(Request::create('GET', '/api/nothing'));
    }

    public function test_it_throws_method_not_allowed_when_path_matches_other_method(): void
    {
        $this->router->get('/api/items', fn (): JsonResponse => new JsonResponse([]));

        $this->expectException(MethodNotAllowedHttpException::class);

        $this->router->dispatch(Request::create('DELETE', '/api/items'));
    }
}

final class FakeController
{
    public function show(Request $request): JsonResponse
    {
        return new JsonResponse(['id' => $request->route('id')]);
    }

    public function store(): JsonResponse
    {
        return new JsonResponse(['created' => true], 201);
    }
}
