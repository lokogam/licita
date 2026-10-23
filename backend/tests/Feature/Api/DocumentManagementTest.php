<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Core\Http\Response;
use App\Core\Http\UploadedFile;
use App\Models\TenderDocument;
use App\Support\Clock;
use App\Support\FrozenClock;
use Tests\DatabaseTestCase;
use Tests\Feature\Concerns\CreatesTenders;

final class DocumentManagementTest extends DatabaseTestCase
{
    use CreatesTenders;

    private int $tenderId;

    /** @var list<array<string, mixed>> */
    private array $documents;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->instance(Clock::class, new FrozenClock('2026-10-20 10:00:00'));

        $this->tenderId = $this->json($this->call('POST', '/api/tenders', $this->tenderPayload()))['data']['id'];
        $response = $this->call(
            'POST',
            "/api/tenders/{$this->tenderId}",
            $this->tenderPayload() + ['_method' => 'PUT', 'documents' => [
                ['title' => 'Pliego', 'description' => 'Pliego de condiciones'],
                ['title' => 'Anexos', 'description' => 'Anexos técnicos'],
            ]],
            ['documents' => [['file' => $this->pdfUpload()], ['file' => $this->zipUpload()]]],
        );
        $this->documents = $this->json($response)['data']['documents'];
    }

    public function test_it_updates_title_and_description(): void
    {
        $response = $this->patch($this->documents[0]['id'], ['title' => 'Pliego v2', 'description' => 'Versión final']);

        $this->assertSame(200, $response->status(), $response->content());
        $document = $this->json($response)['data']['documents'][0];
        $this->assertSame('Pliego v2', $document['title']);
        $this->assertSame('Versión final', $document['description']);
        $this->assertSame('pliego.pdf', $document['original_name']);
    }

    public function test_it_replaces_the_file_and_removes_the_previous_one(): void
    {
        $oldPath = $this->storedPath($this->documents[0]['id']);

        $response = $this->patch(
            $this->documents[0]['id'],
            ['title' => 'Pliego', 'description' => 'Pliego de condiciones'],
            $this->pdfUpload('pliego-firmado.pdf'),
        );

        $this->assertSame(200, $response->status(), $response->content());
        $this->assertSame('pliego-firmado.pdf', $this->json($response)['data']['documents'][0]['original_name']);
        $this->assertFileDoesNotExist($oldPath);
        $this->assertFileExists($this->storedPath($this->documents[0]['id']));
    }

    public function test_it_validates_the_document_fields_and_file_type(): void
    {
        $response = $this->patch(
            $this->documents[0]['id'],
            ['title' => '', 'description' => str_repeat('a', 201)],
            $this->upload('MZ fake', 'virus.pdf', 'application/pdf'),
        );

        $this->assertSame(422, $response->status());
        $errors = $this->json($response)['errors'];
        $this->assertSame(['El campo título del documento es obligatorio.'], $errors['title']);
        $this->assertArrayHasKey('description', $errors);
        $this->assertSame(['El archivo documento debe ser de tipo PDF, ZIP.'], $errors['file']);
    }

    public function test_it_deletes_a_document_and_its_file(): void
    {
        $path = $this->storedPath($this->documents[1]['id']);

        $response = $this->call('DELETE', "/api/tenders/{$this->tenderId}/documents/{$this->documents[1]['id']}");

        $this->assertSame(200, $response->status(), $response->content());
        $this->assertCount(1, $this->json($response)['data']['documents']);
        $this->assertFileDoesNotExist($path);
        $this->assertNull(TenderDocument::query()->find($this->documents[1]['id']));
    }

    public function test_it_keeps_at_least_one_document(): void
    {
        $this->call('DELETE', "/api/tenders/{$this->tenderId}/documents/{$this->documents[1]['id']}");

        $response = $this->call('DELETE', "/api/tenders/{$this->tenderId}/documents/{$this->documents[0]['id']}");

        $this->assertSame(422, $response->status());
        $this->assertSame(
            ['La licitación debe conservar al menos 1 documento.'],
            $this->json($response)['errors']['documents'],
        );
        $this->assertFileExists($this->storedPath($this->documents[0]['id']));
    }

    public function test_documents_of_another_tender_are_not_found(): void
    {
        $otherId = $this->json($this->call('POST', '/api/tenders', $this->tenderPayload()))['data']['id'];

        $this->assertSame(
            404,
            $this->call('DELETE', "/api/tenders/{$otherId}/documents/{$this->documents[0]['id']}")->status(),
        );
        $this->assertSame(
            404,
            $this->call('POST', "/api/tenders/{$otherId}/documents/{$this->documents[0]['id']}", [
                '_method' => 'PATCH',
                'title' => 'x',
                'description' => 'y',
            ])->status(),
        );
    }

    /**
     * @param array<string, string> $fields
     */
    private function patch(int $documentId, array $fields, ?UploadedFile $file = null): Response
    {
        return $this->call(
            'POST',
            "/api/tenders/{$this->tenderId}/documents/{$documentId}",
            $fields + ['_method' => 'PATCH'],
            $file !== null ? ['file' => $file] : [],
        );
    }

    private function storedPath(int $documentId): string
    {
        $document = TenderDocument::query()->findOrFail($documentId);

        return getenv('DOCUMENTS_PATH') . '/' . $document->file_path;
    }
}
