<?php

declare(strict_types=1);

namespace App\Core\Http;

use finfo;
use RuntimeException;

/**
 * A file received in a multipart request.
 */
final class UploadedFile
{
    private ?string $detectedMimeType = null;

    public function __construct(
        private readonly string $path,
        private readonly string $originalName,
        private readonly string $clientMimeType,
        private readonly int $size,
        private readonly int $error,
    ) {
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * File name as sent by the client, stripped of any directory component.
     */
    public function originalName(): string
    {
        return basename(str_replace('\\', '/', $this->originalName));
    }

    /**
     * Lower-cased extension taken from the client file name.
     */
    public function extension(): string
    {
        return strtolower(pathinfo($this->originalName(), PATHINFO_EXTENSION));
    }

    public function clientMimeType(): string
    {
        return $this->clientMimeType;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && $this->path !== '' && is_file($this->path);
    }

    /**
     * MIME type detected from the file contents (never trust the client value).
     */
    public function detectedMimeType(): string
    {
        if ($this->detectedMimeType === null && $this->isValid()) {
            $this->detectedMimeType = (string) (new finfo(FILEINFO_MIME_TYPE))->file($this->path);
        }

        return $this->detectedMimeType ?? '';
    }

    /**
     * Moves the file to its final location (the detected MIME type is kept).
     */
    public function moveTo(string $destination): void
    {
        $this->detectedMimeType();

        $directory = dirname($destination);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create directory [{$directory}].");
        }

        $moved = is_uploaded_file($this->path)
            ? move_uploaded_file($this->path, $destination)
            : rename($this->path, $destination);

        if (!$moved) {
            throw new RuntimeException('Unable to store the uploaded file.');
        }
    }
}
