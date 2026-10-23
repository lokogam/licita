<?php

declare(strict_types=1);

namespace App\Support\Unspsc;

/**
 * Writes activities as batched INSERT statements (the bd/02_activities.sql seed).
 */
final class ActivitiesSqlWriter
{
    private const COLUMNS = [
        'segment_code',
        'segment',
        'family_code',
        'family',
        'class_code',
        'class_name',
        'product_code',
        'product',
    ];

    public function __construct(private readonly int $batchSize = 1000)
    {
    }

    /**
     * Writes the SQL to the stream and returns the number of activities written.
     * Rows repeating an already written product code are skipped.
     *
     * @param iterable<array<string, int|string>> $activities
     * @param resource $stream
     */
    public function write(iterable $activities, $stream): int
    {
        fwrite($stream, "SET NAMES utf8mb4;\n\n");

        $seen = [];
        $batch = [];
        $count = 0;

        foreach ($activities as $activity) {
            $code = (int) $activity['product_code'];
            if (isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;

            $batch[] = $this->values($activity);
            $count++;

            if (count($batch) === $this->batchSize) {
                $this->flush($batch, $stream);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->flush($batch, $stream);
        }

        return $count;
    }

    /**
     * @param list<string> $batch
     * @param resource $stream
     */
    private function flush(array $batch, $stream): void
    {
        fwrite(
            $stream,
            'INSERT INTO activities (' . implode(', ', self::COLUMNS) . ") VALUES\n"
            . implode(",\n", $batch) . ";\n\n",
        );
    }

    /**
     * @param array<string, int|string> $activity
     */
    private function values(array $activity): string
    {
        $values = array_map(
            fn (string $column): string => is_int($activity[$column])
                ? (string) $activity[$column]
                : $this->quote((string) $activity[$column]),
            self::COLUMNS,
        );

        return '(' . implode(', ', $values) . ')';
    }

    private function quote(string $value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "''"], $value) . "'";
    }
}
