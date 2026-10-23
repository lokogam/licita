<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Http\Requests\StoreTenderRequest;
use App\Http\Requests\UpdateTenderRequest;
use App\Http\Resources\TenderResource;
use App\Models\Tender;
use App\Repositories\TenderFilters;
use App\Repositories\TenderRepository;
use App\Services\TenderService;

/**
 * Tenders endpoints: list, create, detail and update.
 */
final class TenderController
{
    private const DEFAULT_PER_PAGE = 10;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private readonly TenderRepository $tenders,
        private readonly TenderService $service,
        private readonly TenderResource $resource,
    ) {
    }

    /**
     * GET /api/tenders - paginated list filtered by consecutive, subject or description.
     */
    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) $request->query('per_page', self::DEFAULT_PER_PAGE)));

        $result = $this->tenders->paginate(TenderFilters::fromRequest($request), $page, $perPage);

        return new JsonResponse([
            'data' => $result->items->map(fn (Tender $tender): array => $this->resource->summary($tender))->all(),
            'meta' => $result->meta(),
        ]);
    }

    /**
     * POST /api/tenders - creates a tender.
     */
    public function store(Request $request, StoreTenderRequest $validation): JsonResponse
    {
        $tender = $this->service->create($validation->validate($request));

        return new JsonResponse(['data' => $this->resource->detail($tender)], 201);
    }

    /**
     * GET /api/tenders/{id} - tender detail.
     */
    public function show(Request $request): JsonResponse
    {
        $tender = $this->tenders->findOrFail((int) $request->route('id'));

        return new JsonResponse(['data' => $this->resource->detail($tender)]);
    }

    /**
     * PUT /api/tenders/{id} - updates a tender and attaches documents (multipart).
     */
    public function update(Request $request, UpdateTenderRequest $validation): JsonResponse
    {
        $tender = $this->tenders->findOrFail((int) $request->route('id'));
        $tender = $this->service->update($tender, $validation->validate($request));

        return new JsonResponse(['data' => $this->resource->detail($tender)]);
    }
}
