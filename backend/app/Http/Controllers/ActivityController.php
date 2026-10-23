<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Repositories\ActivityRepository;

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
    public function show(Request $request): JsonResponse
    {
        $activity = $this->activities->findOrFail((int) $request->route('id'));

        return new JsonResponse(['data' => ActivityResource::make($activity)]);
    }
}
