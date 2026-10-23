<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

/**
 * Real wall-clock time in the application timezone.
 */
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
