<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Repositories\ActivityRepository;
use OpenApi\Attributes as OA;

/**
 * UNSPSC activities endpoints used by the tender form.
 */
final class ActivityController
{
    private const DEFAULT_LIMIT = 20;

    private const MAX_LIMIT = 50;

    public function __construct(private readonly ActivityRepository $activities)
    {
    }

    /**
     * GET /api/activities?search= - autocomplete search.
     */
    #[OA\Get(
        path: '/api/activities',
        summary: 'Buscar actividades (autocompletado)',
        tags: ['Actividades'],
        parameters: [
            new OA\QueryParameter(
                name: 'search',
                description: 'Nombre del producto o prefijo del código (mínimo 2 caracteres)',
                required: true,
                schema: new OA\Schema(type: 'string', minLength: 2),
            ),
            new OA\QueryParameter(
                name: 'limit',
                schema: new OA\Schema(type: 'integer', maximum: 50, minimum: 1, default: 20),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Actividades encontradas',
                content: new OA\JsonContent(properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Activity'),
                    ),
                ]),
            ),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search', '');
        $limit = min(self::MAX_LIMIT, max(1, (int) $request->query('limit', self::DEFAULT_LIMIT)));

        $activities = $this->activities->search(is_string($search) ? $search : '', $limit);

        return new JsonResponse([
            'data' => $activities->map(fn (Activity $activity): array => ActivityResource::make($activity))->all(),
        ]);
    }

    /**
     * GET /api/activities/{id} - single activity.
     */
    #[OA\Get(
        path: '/api/activities/{id}',
        summary: 'Detalle de una actividad',
        tags: ['Actividades'],
        parameters: [
            new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Actividad',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Activity'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function show(Request $request): JsonResponse
    {
        $activity = $this->activities->findOrFail((int) $request->route('id'));

        return new JsonResponse(['data' => ActivityResource::make($activity)]);
    }
}
