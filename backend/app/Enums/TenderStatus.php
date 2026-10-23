<?php

declare(strict_types=1);

namespace App\Enums;

use DateTimeImmutable;

/**
 * Lifecycle status of a tender, derived from its schedule.
 */
enum TenderStatus: string
{
    case Scheduled = 'scheduled';
    case Open = 'open';
    case Closed = 'closed';

    /**
     * Scheduled before the start moment, open until the closing moment, closed afterwards.
     */
    public static function fromSchedule(
        DateTimeImmutable $start,
        DateTimeImmutable $closing,
        ?DateTimeImmutable $now = null,
    ): self {
        $now ??= new DateTimeImmutable();

        return match (true) {
            $now < $start => self::Scheduled,
            $now < $closing => self::Open,
            default => self::Closed,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programada',
            self::Open => 'Abierta',
            self::Closed => 'Cerrada',
        };
    }
}
