<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Http\UploadedFile;
use InvalidArgumentException;

/**
 * Stores tender documents outside the public web root with random file names.
 */
final class DocumentStorage
{
    public function __construct(private readonly string $basePath)
    {
    }

    /**
     * Persists the upload and returns its path relative to the storage root.
     */
    public function store(UploadedFile $file, int $tenderId): string
    {
        $relative = sprintf('tenders/%d/%s.%s', $tenderId, bin2hex(random_bytes(16)), $file->extension());

        $file->moveTo($this->path($relative));

        return $relative;
    }

    public function delete(string $relativePath): void
    {
        $path = $this->path($relativePath);

        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Absolute path of a stored document; rejects any attempt to leave the storage root.
     */
    public function path(string $relativePath): string
    {
        if (str_contains($relativePath, '..') || str_starts_with($relativePath, '/')) {
            throw new InvalidArgumentException('Invalid document path.');
        }

        return rtrim($this->basePath, '/\\') . '/' . $relativePath;
    }
}
