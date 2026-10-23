<?php

declare(strict_types=1);

namespace App\Core\Http;

use RuntimeException;

/**
 * Sends a file from disk as an attachment.
 */
final class FileResponse extends Response
{
    public function __construct(
        private readonly string $path,
        string $downloadName,
        string $contentType,
        private readonly bool $deleteAfterSend = false,
    ) {
        if (!is_file($path)) {
            throw new RuntimeException('File to send does not exist.');
        }

        parent::__construct('', 200, [
            'Content-Type' => $contentType,
            'Content-Length' => (string) filesize($path),
            'Content-Disposition' => self::disposition($downloadName),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * File contents (used by tests; send() streams instead of loading it in memory).
     */
    public function content(): string
    {
        return (string) file_get_contents($this->path);
    }

    protected function sendBody(): void
    {
        readfile($this->path);

        if ($this->deleteAfterSend) {
            @unlink($this->path);
        }
    }

    /**
     * Builds an attachment header with an ASCII fallback and the UTF-8 name (RFC 6266).
     */
    private static function disposition(string $name): string
    {
        $name = str_replace(["\r", "\n", '"', '\\', '/'], '', $name);
        $ascii = (string) preg_replace('/[^\x20-\x7E]/', '_', $name);

        return sprintf('attachment; filename="%s"; filename*=UTF-8\'\'%s', $ascii, rawurlencode($name));
    }
}
