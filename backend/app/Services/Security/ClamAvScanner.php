<?php

declare(strict_types=1);

namespace App\Services\Security;

use Closure;
use RuntimeException;

/**
 * ClamAV client using the clamd INSTREAM command: the file is streamed in
 * length-prefixed chunks and clamd answers "stream: OK" or "stream: <signature> FOUND".
 */
final class ClamAvScanner implements VirusScanner
{
    /**
     * @param Closure(): ClamdConnection $connect
     */
    public function __construct(
        private readonly Closure $connect,
        private readonly int $chunkSize = 8192,
    ) {
    }

    public static function tcp(string $host, int $port): self
    {
        return new self(fn (): ClamdConnection => new TcpClamdConnection($host, $port));
    }

    public function scan(string $path): ScanResult
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Unable to read [{$path}] for scanning.");
        }

        $connection = ($this->connect)();
        try {
            $connection->write("zINSTREAM\0");
            while (!feof($handle)) {
                $chunk = (string) fread($handle, max(1, $this->chunkSize));
                if ($chunk !== '') {
                    $connection->write(pack('N', strlen($chunk)) . $chunk);
                }
            }
            $connection->write(pack('N', 0));

            return $this->parse($connection->readResponse());
        } finally {
            fclose($handle);
            $connection->close();
        }
    }

    private function parse(string $response): ScanResult
    {
        $response = trim($response, "\0\n ");

        if (str_ends_with($response, ': OK')) {
            return ScanResult::clean();
        }

        if (preg_match('/^stream: (.+) FOUND$/', $response, $matches) === 1) {
            return ScanResult::infected($matches[1]);
        }

        throw new ScannerUnavailableException("Unexpected clamd response: {$response}");
    }
}
