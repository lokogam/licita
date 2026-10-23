<?php

declare(strict_types=1);

namespace App\Support\Unspsc;

/**
 * Converts a row of the UNSPSC Spanish classifier spreadsheet into activity columns.
 *
 * Expected column order: segment code, segment, family code, family,
 * class code, class, product code, product.
 */
final class UnspscRowParser
{
    private const NAME_MAX_LENGTH = 200;

    /**
     * Returns the activity attributes, or null for titles, headers and incomplete rows.
     *
     * @param array<int, mixed> $row
     * @return array{segment_code: int, segment: string, family_code: int, family: string,
     *     class_code: int, class_name: string, product_code: int, product: string}|null
     */
    public function parse(array $row): ?array
    {
        $codes = [$row[0] ?? null, $row[2] ?? null, $row[4] ?? null, $row[6] ?? null];
        $names = [$row[1] ?? null, $row[3] ?? null, $row[5] ?? null, $row[7] ?? null];

        foreach ($codes as $code) {
            if (!is_numeric($code)) {
                return null;
            }
        }

        $names = array_map(fn (mixed $name): string => $this->name($name), $names);
        if (in_array('', $names, true)) {
            return null;
        }

        return [
            'segment_code' => (int) $codes[0],
            'segment' => $names[0],
            'family_code' => (int) $codes[1],
            'family' => $names[1],
            'class_code' => (int) $codes[2],
            'class_name' => $names[2],
            'product_code' => (int) $codes[3],
            'product' => $names[3],
        ];
    }

    private function name(mixed $value): string
    {
        $text = is_scalar($value) ? trim((string) preg_replace('/\s+/u', ' ', (string) $value)) : '';

        return mb_substr($text, 0, self::NAME_MAX_LENGTH);
    }
}
