<?php

declare(strict_types=1);

namespace App\Services\Security;

/**
 * Scanner used when no antivirus is available (e.g. hosting without enough memory for clamd).
 */
final class NullScanner implements VirusScanner
{
    public function scan(string $path): ScanResult
    {
        return ScanResult::clean();
    }
}
