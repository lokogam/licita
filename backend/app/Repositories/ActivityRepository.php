<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Exceptions\NotFoundHttpException;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Queries over the UNSPSC activities catalog.
 */
final class ActivityRepository
{
    public const MIN_SEARCH_LENGTH = 2;

    private const COLUMNS = ['id', 'product_code', 'product', 'class_name', 'family', 'segment'];

    /**
     * Autocomplete search: by product code prefix when numeric, by product name otherwise.
     *
     * @return Collection<int, Activity>
     */
    public function search(string $term, int $limit): Collection
    {
        $term = trim($term);

        if (mb_strlen($term) < self::MIN_SEARCH_LENGTH) {
            return new Collection();
        }

        $escaped = addcslashes($term, '%_\\');

        /** @var Builder<Activity> $query */
        $query = Activity::query()->select(self::COLUMNS);

        if (ctype_digit($term)) {
            $query->where('product_code', 'like', $escaped . '%')->orderBy('product_code');
        } else {
            $query->where('product', 'like', '%' . $escaped . '%')->orderBy('product');
        }

        $query->limit($limit);

        return $query->get();
    }

    public function findOrFail(int $id): Activity
    {
        $activity = Activity::query()->select(self::COLUMNS)->find($id);

        if (!$activity instanceof Activity) {
            throw new NotFoundHttpException('La actividad solicitada no existe.');
        }

        return $activity;
    }
}
