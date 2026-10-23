<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Http\FileResponse;
use App\Core\Http\Request;
use App\Models\TenderDocument;
use App\Services\DocumentStorage;

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
