<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Enums\TenderStatus;

/**
 * Search criteria for the tenders list and its Excel export.
 */
final class TenderFilters
{
    public function __construct(
        public readonly ?string $consecutive = null,
        public readonly ?string $subject = null,
        public readonly ?string $description = null,
        public readonly ?TenderStatus $status = null,
    ) {
    }

    /**
     * @throws ValidationException when the status filter is not a known status
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            self::text($request->query('consecutive')),
            self::text($request->query('subject')),
            self::text($request->query('description')),
            self::status($request->query('status')),
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

    private static function status(mixed $value): ?TenderStatus
    {
        if ($value === null || $value === '') {
            return null;
        }

        $status = is_string($value) ? TenderStatus::tryFrom($value) : null;

        if ($status === null) {
            $allowed = implode(', ', array_map(fn (TenderStatus $s): string => $s->value, TenderStatus::cases()));
            throw new ValidationException(['status' => ["El campo estado debe ser uno de: {$allowed}."]]);
        }

        return $status;
    }
}
