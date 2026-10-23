<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Exceptions\NotFoundHttpException;
use App\Enums\TenderStatus;
use App\Models\Tender;
use App\Support\Clock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read queries for tenders.
 */
final class TenderRepository
{
    /** Columns needed by the list view and the Excel export. */
    private const LIST_COLUMNS = [
        'id',
        'consecutive',
        'subject',
        'description',
        'start_date',
        'start_time',
        'closing_date',
        'closing_time',
        'status',
    ];

    public function __construct(private readonly Clock $clock)
    {
    }

    public function findOrFail(int $id): Tender
    {
        $tender = Tender::query()->with(['activity', 'documents'])->find($id);

        if (!$tender instanceof Tender) {
            throw new NotFoundHttpException('La licitación solicitada no existe.');
        }

        return $tender;
    }

    /**
     * Page of tenders matching the filters, newest first.
     *
     * @return Page<Tender>
     */
    public function paginate(TenderFilters $filters, int $page, int $perPage): Page
    {
        $query = $this->filtered($filters);
        $total = (clone $query)->count();

        /** @var Collection<int, Tender> $items */
        $items = $query->forPage($page, $perPage)->get();

        return new Page($items, $total, $page, $perPage);
    }

    /**
     * Every tender matching the filters (no pagination), newest first.
     *
     * @return Collection<int, Tender>
     */
    public function all(TenderFilters $filters): Collection
    {
        return $this->filtered($filters)->get();
    }

    /**
     * @return Builder<Tender>
     */
    private function filtered(TenderFilters $filters): Builder
    {
        /** @var Builder<Tender> $query */
        $query = Tender::query()->select(self::LIST_COLUMNS)->orderByDesc('id');

        foreach (['consecutive', 'subject', 'description'] as $column) {
            $term = $filters->{$column};
            if ($term !== null) {
                $query->where($column, 'like', '%' . self::escapeLike($term) . '%');
            }
        }

        if ($filters->status !== null) {
            $this->whereStatus($query, $filters->status);
        }

        return $query;
    }

    /**
     * Translates a status into ranges over the indexed starts_at / closes_at columns.
     * Mirrors TenderStatus::fromSchedule: open from the start instant, closed from the closing instant.
     *
     * @param Builder<Tender> $query
     */
    private function whereStatus(Builder $query, TenderStatus $status): void
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        match ($status) {
            TenderStatus::Scheduled => $query->where('starts_at', '>', $now),
            TenderStatus::Open => $query->where('starts_at', '<=', $now)->where('closes_at', '>', $now),
            TenderStatus::Closed => $query->where('closes_at', '<=', $now),
        };
    }

    /**
     * Makes %, _ and \ match literally inside LIKE patterns.
     */
    private static function escapeLike(string $term): string
    {
        return addcslashes($term, '%_\\');
    }
}
