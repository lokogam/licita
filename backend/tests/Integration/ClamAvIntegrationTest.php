<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\Security\ClamAvScanner;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Talks to a real clamd (docker compose service "clamav"). Run with: composer test:antivirus
 */
#[Group('antivirus')]
final class ClamAvIntegrationTest extends TestCase
{
    /**
     * Standard EICAR anti-virus test string: harmless, detected by every scanner.
     */
    private const EICAR = 'X5O!P%@AP[4\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

    private ClamAvScanner $scanner;

    protected function setUp(): void
    {
        $this->scanner = ClamAvScanner::tcp(
            (string) (getenv('CLAMAV_HOST') ?: '127.0.0.1'),
            (int) (getenv('CLAMAV_PORT') ?: 3310),
        );
    }

    public function test_it_detects_the_eicar_test_file(): void
    {
        $result = $this->scanner->scan($this->file(self::EICAR));

        $this->assertFalse($result->clean);
        $this->assertStringContainsString('Eicar', (string) $result->signature);
    }

    public function test_it_accepts_a_clean_pdf(): void
    {
        $result = $this->scanner->scan($this->file("%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n"));

        $this->assertTrue($result->clean);
    }

    private function file(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'av');
        file_put_contents($path, $contents);

        return $path;
    }
}
