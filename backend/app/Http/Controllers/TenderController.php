<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Http\FileResponse;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Http\Requests\StoreTenderRequest;
use App\Http\Requests\UpdateTenderRequest;
use App\Http\Resources\TenderResource;
use App\Models\Tender;
use App\Repositories\TenderFilters;
use App\Repositories\TenderRepository;
use App\Services\TenderService;
use App\Support\Clock;
use App\Views\Exports\TendersExport;
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        path: '/api/tenders',
        summary: 'Listar licitaciones (paginado y filtrable)',
        tags: ['Licitaciones'],
        parameters: [
            new OA\QueryParameter(name: 'consecutive', description: 'Contiene', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'subject', description: 'Objeto', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(
                name: 'description',
                description: 'Descripción contiene',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\QueryParameter(
                name: 'status',
                description: 'Estado calculado según el cronograma',
                schema: new OA\Schema(type: 'string', enum: ['scheduled', 'open', 'closed']),
            ),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\QueryParameter(
                name: 'per_page',
                schema: new OA\Schema(type: 'integer', maximum: 100, minimum: 1, default: 10),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Página de licitaciones, más recientes primero',
                content: new OA\JsonContent(properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/TenderSummary'),
                    ),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ]),
            ),
        ],
    )]
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
     * GET /api/tenders/export - Excel with every tender matching the list filters (no pagination).
     */
    #[OA\Get(
        path: '/api/tenders/export',
        summary: 'Exportar a Excel las licitaciones filtradas (sin paginación)',
        tags: ['Licitaciones'],
        parameters: [
            new OA\QueryParameter(name: 'consecutive', description: 'Contiene', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'subject', description: 'Objeto', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(
                name: 'description',
                description: 'Descripción contiene',
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\QueryParameter(
                name: 'status',
                description: 'Estado calculado según el cronograma',
                schema: new OA\Schema(type: 'string', enum: ['scheduled', 'open', 'closed']),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Archivo .xlsx',
                content: new OA\MediaType(
                    mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    schema: new OA\Schema(type: 'string', format: 'binary'),
                ),
            ),
        ],
    )]
    public function export(Request $request, TendersExport $export, Clock $clock): FileResponse
    {
        $path = $export->render($this->tenders->all(TenderFilters::fromRequest($request)));

        return new FileResponse(
            $path,
            'licitaciones-' . $clock->now()->format('Ymd-His') . '.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            deleteAfterSend: true,
        );
    }

    /**
     * POST /api/tenders - creates a tender.
     */
    #[OA\Post(
        path: '/api/tenders',
        summary: 'Crear licitación (el consecutivo O-0001-YY lo genera el servidor)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TenderInput'),
        ),
        tags: ['Licitaciones'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Licitación creada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Tender'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/UnprocessableEntity', response: 422),
        ],
    )]
    public function store(Request $request, StoreTenderRequest $validation): JsonResponse
    {
        $tender = $this->service->create($validation->validate($request));

        return new JsonResponse(['data' => $this->resource->detail($tender)], 201);
    }

    /**
     * GET /api/tenders/{id} - tender detail.
     */
    #[OA\Get(
        path: '/api/tenders/{id}',
        summary: 'Detalle de una licitación',
        tags: ['Licitaciones'],
        parameters: [
            new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Licitación con actividad y documentos',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Tender'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function show(Request $request): JsonResponse
    {
        $tender = $this->tenders->findOrFail((int) $request->route('id'));

        return new JsonResponse(['data' => $this->resource->detail($tender)]);
    }

    /**
     * PUT /api/tenders/{id} - updates a tender and attaches documents (multipart).
     */
    #[OA\Put(
        path: '/api/tenders/{id}',
        description: 'Se envía como multipart/form-data. Desde un navegador use POST con el campo _method=PUT. '
            . 'Debe quedar al menos 1 documento (existentes + nuevos). Archivos permitidos: PDF o ZIP, máx. 10 MB.',
        summary: 'Editar licitación y agregar documentos',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/TenderInput'),
                        new OA\Schema(properties: [
                            new OA\Property(property: '_method', type: 'string', example: 'PUT'),
                            new OA\Property(property: 'documents[0][title]', type: 'string', maxLength: 100),
                            new OA\Property(property: 'documents[0][description]', type: 'string', maxLength: 200),
                            new OA\Property(property: 'documents[0][file]', type: 'string', format: 'binary'),
                        ]),
                    ],
                ),
            ),
        ),
        tags: ['Licitaciones'],
        parameters: [
            new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Licitación actualizada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Tender'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/UnprocessableEntity', response: 422),
        ],
    )]
    public function update(Request $request, UpdateTenderRequest $validation): JsonResponse
    {
        $tender = $this->tenders->findOrFail((int) $request->route('id'));
        $tender = $this->service->update($tender, $validation->validate($request));

        return new JsonResponse(['data' => $this->resource->detail($tender)]);
    }
}
