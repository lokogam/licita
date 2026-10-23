<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\ConsecutiveGenerator;
use DateTimeImmutable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\DatabaseTestCase;

final class ConsecutiveGeneratorDatabaseTest extends DatabaseTestCase
{
    public function test_it_increments_within_the_same_year(): void
    {
        $generator = new ConsecutiveGenerator();
        $now = new DateTimeImmutable('2026-05-10');

        $this->assertSame('O-0001-26', $generator->next($now));
        $this->assertSame('O-0002-26', $generator->next($now));
        $this->assertSame('O-0003-26', $generator->next($now));
    }

    public function test_it_restarts_every_year_without_touching_other_years(): void
    {
        $generator = new ConsecutiveGenerator();

        $generator->next(new DateTimeImmutable('2026-12-31'));
        $generator->next(new DateTimeImmutable('2026-12-31'));

        $this->assertSame('O-0001-27', $generator->next(new DateTimeImmutable('2027-01-01')));
        $this->assertSame('O-0003-26', $generator->next(new DateTimeImmutable('2026-12-31')));
        $this->assertSame(
            2,
            Capsule::table('tender_sequences')->count(),
        );
    }
}
