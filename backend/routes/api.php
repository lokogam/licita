<?php

declare(strict_types=1);

use App\Core\Routing\Router;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\TenderDocumentController;
use App\Http\Controllers\UserController;

/**
 * API route definitions.
 *
 * Everything except health, docs and the session endpoints requires a valid access token;
 * "can:<ability>" checks the role permissions defined in App\Enums\Role.
 *
 * @var Router $router
 */
$router->group('/api', function (Router $router): void {
    $router->get('/health', [HealthController::class, '__invoke']);
    $router->get('/openapi.json', [DocsController::class, 'openapi']);
    $router->get('/docs', [DocsController::class, 'ui']);

    $router->post('/auth/login', [AuthController::class, 'login']);
    $router->post('/auth/refresh', [AuthController::class, 'refresh']);
    $router->post('/auth/logout', [AuthController::class, 'logout']);

    $router->group('', function (Router $router): void {
        $router->get('/auth/me', [AuthController::class, 'me']);

        $router->group('', function (Router $router): void {
            $router->get('/activities', [ActivityController::class, 'index']);
            $router->get('/activities/{id:\d+}', [ActivityController::class, 'show']);

            $router->get('/tenders', [TenderController::class, 'index']);
            $router->get('/tenders/export', [TenderController::class, 'export']);
            $router->get('/tenders/{id:\d+}', [TenderController::class, 'show']);
            $router->get(
                '/tenders/{id:\d+}/documents/{document:\d+}/download',
                [TenderDocumentController::class, 'download'],
            );
        }, ['can:tenders.view']);

        $router->group('', function (Router $router): void {
            $router->post('/tenders', [TenderController::class, 'store']);
        }, ['can:tenders.create']);

        $router->group('', function (Router $router): void {
            $router->put('/tenders/{id:\d+}', [TenderController::class, 'update']);
        }, ['can:tenders.update']);

        $router->group('/tenders/{id:\d+}/documents/{document:\d+}', function (Router $router): void {
            $router->patch('/', [TenderDocumentController::class, 'update']);
            $router->delete('/', [TenderDocumentController::class, 'destroy']);
        }, ['can:documents.manage']);

        $router->group('/users', function (Router $router): void {
            $router->get('/', [UserController::class, 'index']);
            $router->post('/', [UserController::class, 'store']);
            $router->patch('/{id:\d+}', [UserController::class, 'update']);
            $router->delete('/{id:\d+}', [UserController::class, 'destroy']);
        }, ['can:users.manage']);
    }, ['auth']);
});
