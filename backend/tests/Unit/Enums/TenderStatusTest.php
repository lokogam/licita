<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\TenderStatus;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TenderStatusTest extends TestCase
{
    /**
     * @return iterable<string, array{string, TenderStatus}>
     */
    public static function moments(): iterable
    {
        yield 'before start' => ['2026-03-01 07:59:59', TenderStatus::Scheduled];
        yield 'exactly at start' => ['2026-03-01 08:00:00', TenderStatus::Open];
        yield 'between start and closing' => ['2026-03-10 12:00:00', TenderStatus::Open];
        yield 'exactly at closing' => ['2026-03-15 17:00:00', TenderStatus::Closed];
        yield 'after closing' => ['2026-04-01 00:00:00', TenderStatus::Closed];
    }

    #[DataProvider('moments')]
    public function test_it_is_derived_from_the_schedule(string $now, TenderStatus $expected): void
    {
        $status = TenderStatus::fromSchedule(
            new DateTimeImmutable('2026-03-01 08:00:00'),
            new DateTimeImmutable('2026-03-15 17:00:00'),
            new DateTimeImmutable($now),
        );

        $this->assertSame($expected, $status);
    }

    public function test_it_exposes_spanish_labels(): void
    {
        $this->assertSame('Programada', TenderStatus::Scheduled->label());
        $this->assertSame('Abierta', TenderStatus::Open->label());
        $this->assertSame('Cerrada', TenderStatus::Closed->label());
    }
}
