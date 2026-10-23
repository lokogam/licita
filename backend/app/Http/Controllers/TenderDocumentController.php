<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Http\FileResponse;
use App\Core\Http\Request;
use App\Models\TenderDocument;
use App\Services\DocumentStorage;
use OpenApi\Attributes as OA;

/**
 * Serves stored tender documents (they are never exposed from the public folder).
 */
final class TenderDocumentController
{
    public function __construct(private readonly DocumentStorage $storage)
    {
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
        $document = TenderDocument::query()
            ->where('tender_id', (int) $request->route('id'))
            ->find((int) $request->route('document'));

        $path = $document instanceof TenderDocument ? $this->storage->path($document->file_path) : null;

        if ($document === null || $path === null || !is_file($path)) {
            throw new NotFoundHttpException('El documento solicitado no existe.');
        }

        return new FileResponse($path, $document->original_name, $document->mime_type);
    }
}
