<?php

declare(strict_types=1);

namespace App\Services\Security;

/**
 * Byte-level connection to a clamd daemon.
 */
interface ClamdConnection
{
    public function write(string $bytes): void;

    /**
     * Reads until the NUL terminator sent by clamd.
     */
    public function readResponse(): string;

    public function close(): void;
}
