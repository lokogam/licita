<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Liveness probe.
 */
final class HealthController
{
    #[OA\Get(
        path: '/api/health',
        summary: 'Estado del servicio',
        tags: ['Sistema'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Servicio disponible',
                content: new OA\JsonContent(example: ['status' => 'ok']),
            ),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
