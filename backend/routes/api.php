<?php

declare(strict_types=1);

use App\Core\Http\JsonResponse;
use App\Core\Routing\Router;

/**
 * API route definitions.
 *
 * @var Router $router
 */
$router->group('/api', function (Router $router): void {
    $router->get('/health', fn (): JsonResponse => new JsonResponse(['status' => 'ok']));
});
