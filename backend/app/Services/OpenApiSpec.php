<?php

declare(strict_types=1);

namespace App\Services;

use OpenApi\Generator;
use RuntimeException;

/**
 * Provides the OpenAPI document, generated from the code attributes.
 *
 * In production the document is generated once (at image build time or on first
 * request) and served from a cache file; while debugging it is always regenerated.
 */
final class OpenApiSpec
{
    public function __construct(
        private readonly string $cacheFile,
        private readonly bool $debug,
    ) {
    }

    public function json(): string
    {
        if ($this->debug) {
            return $this->generate();
        }

        if (is_file($this->cacheFile)) {
            return (string) file_get_contents($this->cacheFile);
        }

        return $this->build();
    }

    /**
     * Generates the document and stores it in the cache file.
     */
    public function build(): string
    {
        $json = $this->generate();

        $directory = dirname($this->cacheFile);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create directory [{$directory}].");
        }

        file_put_contents($this->cacheFile, $json, LOCK_EX);

        return $json;
    }

    private function generate(): string
    {
        return (string) (new Generator())->generate([dirname(__DIR__)])?->toJson();
    }
}
