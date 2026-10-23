<?php

declare(strict_types=1);

use App\Core\Http\JsonResponse;
use App\Core\Routing\Router;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\TenderController;

/**
 * API route definitions.
 *
 * @var Router $router
 */
$router->group('/api', function (Router $router): void {
    $router->get('/health', fn (): JsonResponse => new JsonResponse(['status' => 'ok']));

    $router->get('/activities', [ActivityController::class, 'index']);
    $router->get('/activities/{id:\d+}', [ActivityController::class, 'show']);

    $router->get('/tenders', [TenderController::class, 'index']);
    $router->post('/tenders', [TenderController::class, 'store']);
    $router->get('/tenders/{id:\d+}', [TenderController::class, 'show']);
    $router->put('/tenders/{id:\d+}', [TenderController::class, 'update']);
});
