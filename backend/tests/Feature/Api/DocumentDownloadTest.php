<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Support\Clock;
use App\Support\FrozenClock;
use Tests\DatabaseTestCase;
use Tests\Feature\Concerns\CreatesTenders;

final class DocumentDownloadTest extends DatabaseTestCase
{
    use CreatesTenders;

    private int $tenderId;

    private int $documentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->instance(Clock::class, new FrozenClock('2026-10-20 10:00:00'));

        $this->tenderId = $this->json($this->call('POST', '/api/tenders', $this->tenderPayload()))['data']['id'];

        $response = $this->call(
            'POST',
            "/api/tenders/{$this->tenderId}",
            $this->tenderPayload() + ['_method' => 'PUT', 'documents' => [['title' => 'Pliego', 'description' => 'Pliego']]],
            ['documents' => [['file' => $this->pdfUpload('Pliego de condiciones.pdf')]]],
        );
        $this->documentId = $this->json($response)['data']['documents'][0]['id'];
    }

    public function test_it_downloads_a_document_with_its_original_name(): void
    {
        $response = $this->call('GET', "/api/tenders/{$this->tenderId}/documents/{$this->documentId}/download");

        $this->assertSame(200, $response->status());
        $this->assertSame('application/pdf', $response->headers()['Content-Type']);
        $this->assertStringContainsString(
            'attachment; filename="Pliego de condiciones.pdf"',
            $response->headers()['Content-Disposition'],
        );
        $this->assertStringStartsWith('%PDF-1.4', $response->content());
    }

    public function test_it_does_not_serve_documents_through_another_tender(): void
    {
        $otherTenderId = $this->json($this->call('POST', '/api/tenders', $this->tenderPayload()))['data']['id'];

        $response = $this->call('GET', "/api/tenders/{$otherTenderId}/documents/{$this->documentId}/download");

        $this->assertSame(404, $response->status());
        $this->assertSame('El documento solicitado no existe.', $this->json($response)['message']);
    }

    public function test_it_returns_404_for_unknown_documents(): void
    {
        $response = $this->call('GET', "/api/tenders/{$this->tenderId}/documents/999999/download");

        $this->assertSame(404, $response->status());
    }
}
