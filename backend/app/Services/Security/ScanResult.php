<?php

declare(strict_types=1);

namespace App\Services\Security;

/**
 * Outcome of a malware scan.
 */
final class ScanResult
{
    private function __construct(
        public readonly bool $clean,
        public readonly ?string $signature,
    ) {
    }

    public static function clean(): self
    {
        return new self(true, null);
    }

    public static function infected(string $signature): self
    {
        return new self(false, $signature);
    }
}
