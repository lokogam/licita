<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\UploadedFile;
use App\Models\TenderDocument;
use Illuminate\Database\ConnectionInterface;
use Throwable;

/**
 * Changes to documents that are already stored.
 *
 * Physical files are only removed after the database transaction commits, so a
 * failed operation never leaves a record pointing to a missing file.
 */
final class TenderDocumentService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly DocumentStorage $storage,
    ) {
    }

    /**
     * Updates title/description and optionally replaces the file.
     *
     * @param array{title: string, description: string, file?: UploadedFile|null} $data
     */
    public function update(TenderDocument $document, array $data): void
    {
        $file = $data['file'] ?? null;
        $previousPath = $document->file_path;
        $newPath = null;

        try {
            $this->db->transaction(function () use ($document, $data, $file, &$newPath): void {
                $document->title = $data['title'];
                $document->description = $data['description'];

                if ($file instanceof UploadedFile) {
                    $newPath = $this->storage->store($file, $document->tender_id);
                    $document->file_path = $newPath;
                    $document->original_name = $file->originalName();
                    $document->mime_type = $file->detectedMimeType();
                    $document->size = $file->size();
                }

                $document->save();
            });
        } catch (Throwable $e) {
            if ($newPath !== null) {
                $this->storage->delete($newPath);
            }

            throw $e;
        }

        if ($newPath !== null) {
            $this->storage->delete($previousPath);
        }
    }

    /**
     * Deletes a document; a tender must always keep at least one.
     *
     * @throws ValidationException when it is the last document of the tender
     */
    public function delete(TenderDocument $document): void
    {
        $this->db->transaction(function () use ($document): void {
            $remaining = TenderDocument::query()
                ->where('tender_id', $document->tender_id)
                ->lockForUpdate()
                ->count();

            if ($remaining <= 1) {
                throw new ValidationException(['documents' => ['La licitación debe conservar al menos 1 documento.']]);
            }

            $document->delete();
        });

        $this->storage->delete($document->file_path);
    }
}
