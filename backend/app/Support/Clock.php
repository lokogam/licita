<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;

/**
 * Source of the current time (injectable so business rules can be tested).
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
