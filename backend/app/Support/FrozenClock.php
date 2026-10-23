<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

/**
 * Clock fixed at a given moment (tests and reproducible scripts).
 */
final class FrozenClock implements Clock
{
    private readonly DateTimeImmutable $now;

    public function __construct(string $moment)
    {
        $this->now = new DateTimeImmutable($moment);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
