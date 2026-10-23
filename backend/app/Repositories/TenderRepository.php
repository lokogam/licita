<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Exceptions\NotFoundHttpException;
use App\Models\Tender;
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

        return $query;
    }

    /**
     * Makes %, _ and \ match literally inside LIKE patterns.
     */
    private static function escapeLike(string $term): string
    {
        return addcslashes($term, '%_\\');
    }
}
