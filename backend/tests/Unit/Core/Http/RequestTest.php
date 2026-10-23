<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Http;

use App\Core\Http\Request;
use App\Core\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function test_it_normalizes_method_and_path(): void
    {
        $request = Request::create('get', '/api/tenders/?page=2');

        $this->assertSame('GET', $request->method());
        $this->assertSame('/api/tenders', $request->path());
        $this->assertSame('2', $request->query('page'));
    }

    public function test_it_merges_query_and_body_in_input(): void
    {
        $request = Request::create('POST', '/api/tenders?page=1', body: ['subject' => 'Compra']);

        $this->assertSame('Compra', $request->input('subject'));
        $this->assertSame('1', $request->input('page'));
        $this->assertNull($request->input('missing'));
        $this->assertSame('x', $request->input('missing', 'x'));
    }

    public function test_it_allows_method_spoofing_on_post_requests(): void
    {
        $request = Request::create('POST', '/api/tenders/1', body: ['_method' => 'put']);

        $this->assertSame('PUT', $request->method());
    }

    public function test_it_ignores_method_spoofing_on_get_requests(): void
    {
        $request = Request::create('GET', '/api/tenders?_method=DELETE');

        $this->assertSame('GET', $request->method());
    }

    public function test_it_reads_headers_case_insensitively(): void
    {
        $request = Request::create('GET', '/', headers: ['Content-Type' => 'application/json']);

        $this->assertSame('application/json', $request->header('content-type'));
    }

    public function test_it_exposes_route_parameters(): void
    {
        $request = Request::create('GET', '/api/tenders/5')->withRouteParams(['id' => '5']);

        $this->assertSame('5', $request->route('id'));
        $this->assertNull($request->route('other'));
    }

    public function test_it_returns_uploaded_files_as_list(): void
    {
        $file = new UploadedFile('/tmp/a', 'a.pdf', 'application/pdf', 10, UPLOAD_ERR_OK);
        $request = Request::create('POST', '/', files: ['documents' => [$file]]);

        $this->assertSame([$file], $request->files('documents'));
        $this->assertSame([], $request->files('missing'));
    }

    public function test_it_normalizes_nested_php_files_array(): void
    {
        $files = Request::normalizeFiles([
            'documents' => [
                'name' => [0 => ['file' => 'a.pdf']],
                'type' => [0 => ['file' => 'application/pdf']],
                'tmp_name' => [0 => ['file' => '/tmp/php1']],
                'error' => [0 => ['file' => UPLOAD_ERR_OK]],
                'size' => [0 => ['file' => 1234]],
            ],
        ]);

        $file = $files['documents'][0]['file'];
        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertSame('a.pdf', $file->originalName());
        $this->assertSame(1234, $file->size());
    }
}
