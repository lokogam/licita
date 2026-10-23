<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use App\Enums\TenderStatus;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tender (oferta / licitación).
 *
 * @property int $id
 * @property string $consecutive
 * @property string $subject
 * @property string $description
 * @property Currency $currency
 * @property string $budget
 * @property int $activity_id
 * @property string $start_date   Y-m-d
 * @property string $start_time   H:i
 * @property string $closing_date Y-m-d
 * @property string $closing_time H:i
 * @property TenderStatus $status
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property-read Activity $activity
 * @property-read Collection<int, TenderDocument> $documents
 */
final class Tender extends Model
{
    protected $table = 'tenders';

    protected $fillable = [
        'consecutive',
        'subject',
        'description',
        'currency',
        'budget',
        'activity_id',
        'start_date',
        'start_time',
        'closing_date',
        'closing_time',
        'status',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'currency' => Currency::class,
        'budget' => 'decimal:2',
        'activity_id' => 'integer',
        'status' => TenderStatus::class,
    ];

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @return HasMany<TenderDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(TenderDocument::class);
    }

    public function startsAt(): DateTimeImmutable
    {
        return new DateTimeImmutable("{$this->start_date} {$this->start_time}");
    }

    public function closesAt(): DateTimeImmutable
    {
        return new DateTimeImmutable("{$this->closing_date} {$this->closing_time}");
    }

    /**
     * Status at a given moment (now by default), always derived from the schedule.
     */
    public function currentStatus(?DateTimeImmutable $now = null): TenderStatus
    {
        return TenderStatus::fromSchedule($this->startsAt(), $this->closesAt(), $now);
    }

    /**
     * TIME columns are exposed as HH:mm.
     *
     * @return Attribute
     */
    protected function startTime(): Attribute
    {
        return Attribute::get(fn (?string $value): string => substr((string) $value, 0, 5));
    }

    /**
     * @return Attribute
     */
    protected function closingTime(): Attribute
    {
        return Attribute::get(fn (?string $value): string => substr((string) $value, 0, 5));
    }
}
