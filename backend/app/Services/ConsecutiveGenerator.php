<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;

/**
 * Issues tender consecutives with the format O-{number padded to 4}-{YY}.
 *
 * The counter restarts every year. The upsert locks the year row until the
 * surrounding transaction commits, so concurrent requests never get the same number.
 */
final class ConsecutiveGenerator
{
    private readonly ConnectionInterface $db;

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db = $db ?? Capsule::connection();
    }

    /**
     * Reserves and returns the next consecutive for the year of the given date.
     */
    public function next(DateTimeImmutable $at): string
    {
        $year = (int) $at->format('Y');

        return $this->db->transaction(function () use ($year): string {
            $this->db->statement(
                'INSERT INTO tender_sequences (year, last_number) VALUES (?, 1)
                 ON DUPLICATE KEY UPDATE last_number = last_number + 1',
                [$year],
            );

            $number = (int) $this->db->table('tender_sequences')->where('year', $year)->value('last_number');

            return self::format($number, $year);
        });
    }

    public static function format(int $number, int $year): string
    {
        return sprintf('O-%04d-%02d', $number, $year % 100);
    }
}
