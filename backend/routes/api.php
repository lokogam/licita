<?php

declare(strict_types=1);

use App\Core\Routing\Router;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\TenderDocumentController;

/**
 * API route definitions.
 *
 * @var Router $router
 */
$router->group('/api', function (Router $router): void {
    $router->get('/health', [HealthController::class, '__invoke']);
    $router->get('/openapi.json', [DocsController::class, 'openapi']);
    $router->get('/docs', [DocsController::class, 'ui']);

    $router->get('/activities', [ActivityController::class, 'index']);
    $router->get('/activities/{id:\d+}', [ActivityController::class, 'show']);

    $router->get('/tenders', [TenderController::class, 'index']);
    $router->get('/tenders/export', [TenderController::class, 'export']);
    $router->post('/tenders', [TenderController::class, 'store']);
    $router->get('/tenders/{id:\d+}', [TenderController::class, 'show']);
    $router->put('/tenders/{id:\d+}', [TenderController::class, 'update']);
    $router->get(
        '/tenders/{id:\d+}/documents/{document:\d+}/download',
        [TenderDocumentController::class, 'download'],
    );
});
