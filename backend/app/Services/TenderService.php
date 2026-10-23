<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Http\UploadedFile;
use App\Enums\Currency;
use App\Models\Tender;
use App\Models\TenderDocument;
use App\Support\Clock;
use Illuminate\Database\ConnectionInterface;
use Throwable;

/**
 * Business operations on tenders.
 */
final class TenderService
{
    private const FIELDS = [
        'subject',
        'description',
        'currency',
        'budget',
        'activity_id',
        'start_date',
        'start_time',
        'closing_date',
        'closing_time',
    ];

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly ConsecutiveGenerator $consecutives,
        private readonly DocumentStorage $storage,
        private readonly Clock $clock,
    ) {
    }

    /**
     * Creates a tender assigning its consecutive and initial status.
     *
     * @param array<string, mixed> $data Validated data.
     */
    public function create(array $data): Tender
    {
        return $this->db->transaction(function () use ($data): Tender {
            $tender = new Tender($this->attributes($data));
            $tender->consecutive = $this->consecutives->next($this->clock->now());
            $tender->status = $tender->currentStatus($this->clock->now());
            $tender->save();

            return $this->fresh($tender);
        });
    }

    /**
     * Updates the tender fields and attaches the new documents.
     * Stored files are removed again if anything fails.
     *
     * @param array<string, mixed> $data Validated data, documents under "documents".
     */
    public function update(Tender $tender, array $data): Tender
    {
        $storedPaths = [];

        try {
            return $this->db->transaction(function () use ($tender, $data, &$storedPaths): Tender {
                $tender->fill($this->attributes($data));
                $tender->status = $tender->currentStatus($this->clock->now());
                $tender->save();

                /** @var list<array{title: string, description: string, file: UploadedFile}> $documents */
                $documents = $data['documents'] ?? [];
                foreach ($documents as $document) {
                    $path = $this->storage->store($document['file'], $tender->id);
                    $storedPaths[] = $path;

                    TenderDocument::query()->create([
                        'tender_id' => $tender->id,
                        'title' => $document['title'],
                        'description' => $document['description'],
                        'file_path' => $path,
                        'original_name' => $document['file']->originalName(),
                        'mime_type' => $document['file']->detectedMimeType(),
                        'size' => $document['file']->size(),
                    ]);
                }

                return $this->fresh($tender);
            });
        } catch (Throwable $e) {
            array_walk($storedPaths, fn (string $path) => $this->storage->delete($path));

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $attributes = array_intersect_key($data, array_flip(self::FIELDS));
        $attributes['currency'] = Currency::from((string) $attributes['currency']);
        $attributes['activity_id'] = (int) $attributes['activity_id'];

        return $attributes;
    }

    private function fresh(Tender $tender): Tender
    {
        return $tender->refresh()->load(['activity', 'documents']);
    }
}
