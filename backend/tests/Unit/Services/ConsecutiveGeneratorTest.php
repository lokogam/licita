<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ConsecutiveGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConsecutiveGeneratorTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int, string}>
     */
    public static function numbers(): iterable
    {
        yield 'first of the year' => [1, 2026, 'O-0001-26'];
        yield 'two digits' => [42, 2026, 'O-0042-26'];
        yield 'four digits' => [9999, 2026, 'O-9999-26'];
        yield 'grows past four digits' => [10000, 2026, 'O-10000-26'];
        yield 'year with leading zero' => [7, 2105, 'O-0007-05'];
    }

    #[DataProvider('numbers')]
    public function test_it_formats_the_consecutive(int $number, int $year, string $expected): void
    {
        $this->assertSame($expected, ConsecutiveGenerator::format($number, $year));
    }
}
