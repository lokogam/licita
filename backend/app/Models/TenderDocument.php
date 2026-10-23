<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File attached to a tender (PDF or ZIP).
 *
 * @property int $id
 * @property int $tender_id
 * @property string $title
 * @property string $description
 * @property string $file_path   Path relative to the documents storage directory.
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property CarbonInterface $created_at
 */
final class TenderDocument extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'tender_documents';

    protected $fillable = [
        'tender_id',
        'title',
        'description',
        'file_path',
        'original_name',
        'mime_type',
        'size',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * @return BelongsTo<Tender, $this>
     */
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }
}
