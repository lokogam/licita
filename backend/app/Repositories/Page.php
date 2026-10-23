<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * One page of results plus the numbers needed to paginate.
 *
 * @template TModel of Model
 */
final class Page
{
    /**
     * @param Collection<int, TModel> $items
     */
    public function __construct(
        public readonly Collection $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    /**
     * @return array{current_page: int, per_page: int, total: int, last_page: int, from: int|null, to: int|null}
     */
    public function meta(): array
    {
        $count = $this->items->count();
        $from = $count > 0 ? ($this->page - 1) * $this->perPage + 1 : null;

        return [
            'current_page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'last_page' => max(1, (int) ceil($this->total / $this->perPage)),
            'from' => $from,
            'to' => $from !== null ? $from + $count - 1 : null,
        ];
    }
}
