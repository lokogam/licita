<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tender;
use App\Models\TenderDocument;
use App\Support\Clock;

/**
 * JSON representation of tenders (list row and full detail).
 *
 * The status is always computed at response time from the schedule.
 */
final class TenderResource
{
    public function __construct(private readonly Clock $clock)
    {
    }

    /**
     * Row of the tenders list.
     *
     * @return array<string, mixed>
     */
    public function summary(Tender $tender): array
    {
        return [
            'id' => $tender->id,
            'consecutive' => $tender->consecutive,
            'subject' => $tender->subject,
            'description' => $tender->description,
            'start_date' => $tender->start_date,
            'closing_date' => $tender->closing_date,
            'status' => $this->status($tender),
        ];
    }

    /**
     * Full detail including activity and documents.
     *
     * @return array<string, mixed>
     */
    public function detail(Tender $tender): array
    {
        return [
            'id' => $tender->id,
            'consecutive' => $tender->consecutive,
            'subject' => $tender->subject,
            'description' => $tender->description,
            'currency' => $tender->currency->value,
            'budget' => $tender->budget,
            'activity' => ActivityResource::make($tender->activity),
            'start_date' => $tender->start_date,
            'start_time' => $tender->start_time,
            'closing_date' => $tender->closing_date,
            'closing_time' => $tender->closing_time,
            'status' => $this->status($tender),
            'documents' => $tender->documents
                ->map(fn (TenderDocument $document): array => self::document($tender, $document))
                ->values()
                ->all(),
            'created_at' => $tender->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $tender->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array{value: string, label: string}
     */
    private function status(Tender $tender): array
    {
        $status = $tender->currentStatus($this->clock->now());

        return ['value' => $status->value, 'label' => $status->label()];
    }

    /**
     * @return array<string, mixed>
     */
    private static function document(Tender $tender, TenderDocument $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'description' => $document->description,
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'size' => $document->size,
            'created_at' => $document->created_at->format('Y-m-d H:i:s'),
            'download_url' => "/api/tenders/{$tender->id}/documents/{$document->id}/download",
        ];
    }
}
