<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Services\Security\ClamAvScanner;
use App\Services\Security\ClamdConnection;
use App\Services\Security\ScannerUnavailableException;
use PHPUnit\Framework\TestCase;

final class ClamAvScannerTest extends TestCase
{
    public function test_clean_files_pass(): void
    {
        $result = $this->scanner("stream: OK\0")->scan($this->file('hello'));

        $this->assertTrue($result->clean);
        $this->assertNull($result->signature);
    }

    public function test_infected_files_report_the_signature(): void
    {
        $result = $this->scanner("stream: Eicar-Test-Signature FOUND\0")->scan($this->file('x'));

        $this->assertFalse($result->clean);
        $this->assertSame('Eicar-Test-Signature', $result->signature);
    }

    public function test_it_speaks_the_instream_protocol_in_chunks(): void
    {
        $connection = new FakeClamdConnection("stream: OK\0");

        (new ClamAvScanner(fn (): ClamdConnection => $connection, chunkSize: 4))->scan($this->file('abcdefghij'));

        $this->assertSame(
            "zINSTREAM\0"
            . pack('N', 4) . 'abcd'
            . pack('N', 4) . 'efgh'
            . pack('N', 2) . 'ij'
            . pack('N', 0),
            $connection->written,
        );
        $this->assertTrue($connection->closed);
    }

    public function test_clamd_errors_are_reported_as_unavailable(): void
    {
        $this->expectException(ScannerUnavailableException::class);

        $this->scanner("INSTREAM size limit exceeded. ERROR\0")->scan($this->file('x'));
    }

    public function test_connection_failures_are_reported_as_unavailable(): void
    {
        $scanner = new ClamAvScanner(function (): ClamdConnection {
            throw new ScannerUnavailableException('Connection refused');
        });

        $this->expectException(ScannerUnavailableException::class);

        $scanner->scan($this->file('x'));
    }

    private function scanner(string $response): ClamAvScanner
    {
        return new ClamAvScanner(fn (): ClamdConnection => new FakeClamdConnection($response));
    }

    private function file(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'scan');
        file_put_contents($path, $contents);

        return $path;
    }
}

final class FakeClamdConnection implements ClamdConnection
{
    public string $written = '';

    public bool $closed = false;

    public function __construct(private readonly string $response)
    {
    }

    public function write(string $bytes): void
    {
        $this->written .= $bytes;
    }

    public function readResponse(): string
    {
        return $this->response;
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
