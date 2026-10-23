<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Http\FileResponse;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\UploadedFile;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Resources\TenderResource;
use App\Models\TenderDocument;
use App\Repositories\TenderRepository;
use App\Services\DocumentStorage;
use App\Services\TenderDocumentService;
use OpenApi\Attributes as OA;

/**
 * Stored tender documents: download, edit and delete.
 * Files are never exposed from the public folder.
 */
final class TenderDocumentController
{
    public function __construct(
        private readonly DocumentStorage $storage,
        private readonly TenderDocumentService $documents,
        private readonly TenderRepository $tenders,
        private readonly TenderResource $resource,
    ) {
    }

    /**
     * GET /api/tenders/{id}/documents/{document}/download
     */
    #[OA\Get(
        path: '/api/tenders/{id}/documents/{document}/download',
        summary: 'Descargar un documento de la licitación',
        tags: ['Documentos'],
        parameters: [
            new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'document', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Archivo PDF o ZIP',
                content: [
                    new OA\MediaType(
                        mediaType: 'application/pdf',
                        schema: new OA\Schema(type: 'string', format: 'binary'),
                    ),
                    new OA\MediaType(
                        mediaType: 'application/zip',
                        schema: new OA\Schema(type: 'string', format: 'binary'),
                    ),
                ],
            ),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function download(Request $request): FileResponse
    {
        $document = $this->find($request);
        $path = $this->storage->path($document->file_path);

        if (!is_file($path)) {
            throw new NotFoundHttpException('El documento solicitado no existe.');
        }

        return new FileResponse($path, $document->original_name, $document->mime_type ?: 'application/octet-stream');
    }

    /**
     * PATCH /api/tenders/{id}/documents/{document} - edit title/description, optionally replace the file.
     */
    #[OA\Patch(
        path: '/api/tenders/{id}/documents/{document}',
        description: 'Se envía como multipart/form-data (desde un navegador: POST con _method=PATCH). '
            . 'El archivo es opcional; si se envía reemplaza al actual (PDF o ZIP, máx. 10 MB).',
        summary: 'Editar un documento',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['title', 'description'],
                    properties: [
                        new OA\Property(property: '_method', type: 'string', example: 'PATCH'),
                        new OA\Property(property: 'title', type: 'string', maxLength: 100),
                        new OA\Property(property: 'description', type: 'string', maxLength: 200),
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ],
                ),
            ),
        ),
        tags: ['Documentos'],
        parameters: [
            new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'document', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Licitación con los documentos actualizados',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Tender'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/UnprocessableEntity', response: 422),
        ],
    )]
    public function update(Request $request, UpdateDocumentRequest $validation): JsonResponse
    {
        $document = $this->find($request);

        /** @var array{title: string, description: string, file?: UploadedFile|null} $data */
        $data = $validation->validate($request);
        $this->documents->update($document, $data);

        return $this->tenderResponse($document->tender_id);
    }

    /**
     * DELETE /api/tenders/{id}/documents/{document} - a tender must keep at least one document.
     */
    #[OA\Delete(
        path: '/api/tenders/{id}/documents/{document}',
        summary: 'Eliminar un documento (debe quedar al menos 1)',
        tags: ['Documentos'],
        parameters: [
            new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'document', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Licitación con los documentos restantes',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Tender'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/UnprocessableEntity', response: 422),
        ],
    )]
    public function destroy(Request $request): JsonResponse
    {
        $document = $this->find($request);
        $this->documents->delete($document);

        return $this->tenderResponse($document->tender_id);
    }

    /**
     * Document scoped to the tender in the URL (404 if it belongs to another tender).
     */
    private function find(Request $request): TenderDocument
    {
        $document = TenderDocument::query()
            ->where('tender_id', (int) $request->route('id'))
            ->find((int) $request->route('document'));

        if (!$document instanceof TenderDocument) {
            throw new NotFoundHttpException('El documento solicitado no existe.');
        }

        return $document;
    }

    private function tenderResponse(int $tenderId): JsonResponse
    {
        return new JsonResponse(['data' => $this->resource->detail($this->tenders->findOrFail($tenderId))]);
    }
}
