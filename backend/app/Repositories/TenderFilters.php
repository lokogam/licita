<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Http\Request;

/**
 * Search criteria for the tenders list and its Excel export.
 */
final class TenderFilters
{
    public function __construct(
        public readonly ?string $consecutive = null,
        public readonly ?string $subject = null,
        public readonly ?string $description = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            self::text($request->query('consecutive')),
            self::text($request->query('subject')),
            self::text($request->query('description')),
        );
    }

    private static function text(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 150);
    }
}
