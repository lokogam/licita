<?php

declare(strict_types=1);

namespace App\Services\Security;

/**
 * clamd connection over TCP (default port 3310).
 */
final class TcpClamdConnection implements ClamdConnection
{
    /** @var resource */
    private $socket;

    public function __construct(string $host, int $port, float $timeoutSeconds = 30.0)
    {
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errorCode, $errorMessage, $timeoutSeconds);
        if ($socket === false) {
            throw new ScannerUnavailableException("clamd unreachable at {$host}:{$port}: {$errorMessage}");
        }

        stream_set_timeout($socket, (int) ceil($timeoutSeconds));
        $this->socket = $socket;
    }

    public function write(string $bytes): void
    {
        $length = strlen($bytes);
        $written = 0;
        while ($written < $length) {
            $result = @fwrite($this->socket, substr($bytes, $written));
            if ($result === false || $result === 0) {
                throw new ScannerUnavailableException('Connection to clamd was interrupted.');
            }
            $written += $result;
        }
    }

    public function readResponse(): string
    {
        $response = '';
        while (!feof($this->socket)) {
            $chunk = fread($this->socket, 1024);
            if ($chunk === false || ($chunk === '' && stream_get_meta_data($this->socket)['timed_out'])) {
                throw new ScannerUnavailableException('clamd did not answer in time.');
            }
            $response .= $chunk;
            if (str_contains($response, "\0")) {
                break;
            }
        }

        return $response;
    }

    public function close(): void
    {
        fclose($this->socket);
    }
}
