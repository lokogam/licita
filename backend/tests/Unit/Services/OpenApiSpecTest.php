<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\OpenApiSpec;
use PHPUnit\Framework\TestCase;

final class OpenApiSpecTest extends TestCase
{
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = sys_get_temp_dir() . '/openapi-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->cacheFile);
    }

    public function test_it_serves_the_cached_document_when_not_debugging(): void
    {
        file_put_contents($this->cacheFile, '{"cached":true}');

        $spec = new OpenApiSpec($this->cacheFile, debug: false);

        $this->assertSame('{"cached":true}', $spec->json());
    }

    public function test_it_ignores_the_cache_while_debugging(): void
    {
        file_put_contents($this->cacheFile, '{"cached":true}');

        $json = (new OpenApiSpec($this->cacheFile, debug: true))->json();

        $this->assertStringContainsString('"openapi"', $json);
        $this->assertStringNotContainsString('cached', $json);
    }

    public function test_build_writes_the_generated_document(): void
    {
        $spec = new OpenApiSpec($this->cacheFile, debug: false);

        $spec->build();

        $document = json_decode((string) file_get_contents($this->cacheFile), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('API Módulo de Licitaciones', $document['info']['title']);
    }

    public function test_it_generates_and_stores_the_document_when_the_cache_is_missing(): void
    {
        $json = (new OpenApiSpec($this->cacheFile, debug: false))->json();

        $this->assertFileExists($this->cacheFile);
        $this->assertSame($json, file_get_contents($this->cacheFile));
    }
}
