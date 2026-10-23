<?php

declare(strict_types=1);

namespace App\Services\Security;

/**
 * Scans a file for malware.
 */
interface VirusScanner
{
    /**
     * @throws ScannerUnavailableException when the scan could not be performed
     */
    public function scan(string $path): ScanResult;
}
