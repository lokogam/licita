<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Tender;
use App\Models\TenderDocument;
use App\Support\Clock;
use App\Support\FrozenClock;
use Tests\DatabaseTestCase;
use Tests\Feature\Concerns\CreatesTenders;

final class UpdateTenderTest extends DatabaseTestCase
{
    use CreatesTenders;

    private int $tenderId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->instance(Clock::class, new FrozenClock('2026-10-20 10:00:00'));

        $response = $this->call('POST', '/api/tenders', $this->tenderPayload());
        $this->tenderId = $this->json($response)['data']['id'];
    }

    public function test_it_updates_fields_and_stores_documents(): void
    {
        $response = $this->update(
            $this->tenderPayload(['subject' => 'Compra de portátiles (ajustada)']),
            [
                ['title' => 'Pliego', 'description' => 'Pliego de condiciones', 'file' => $this->pdfUpload()],
                ['title' => 'Anexos', 'description' => 'Anexos técnicos', 'file' => $this->zipUpload()],
            ],
        );

        $this->assertSame(200, $response->status(), $response->content());
        $data = $this->json($response)['data'];
        $this->assertSame('Compra de portátiles (ajustada)', $data['subject']);
        $this->assertSame('O-0001-26', $data['consecutive']);
        $this->assertCount(2, $data['documents']);
        $this->assertSame('pliego.pdf', $data['documents'][0]['original_name']);
        $this->assertSame('application/zip', $data['documents'][1]['mime_type']);
        $this->assertStringEndsWith(
            "/api/tenders/{$this->tenderId}/documents/{$data['documents'][0]['id']}/download",
            $data['documents'][0]['download_url'],
        );

        $stored = TenderDocument::query()->where('tender_id', $this->tenderId)->get();
        foreach ($stored as $document) {
            $this->assertFileExists($this->documentsPath() . '/' . $document->file_path);
            $this->assertStringNotContainsString('pliego', $document->file_path);
        }
    }

    public function test_it_requires_at_least_one_document(): void
    {
        $response = $this->update($this->tenderPayload(), []);

        $this->assertSame(422, $response->status());
        $this->assertSame(
            ['Debe registrar al menos 1 documento.'],
            $this->json($response)['errors']['documents'],
        );
    }

    public function test_existing_documents_satisfy_the_minimum(): void
    {
        $this->update($this->tenderPayload(), [
            ['title' => 'Pliego', 'description' => 'Pliego', 'file' => $this->pdfUpload()],
        ]);

        $response = $this->update($this->tenderPayload(['subject' => 'Otro objeto']), []);

        $this->assertSame(200, $response->status());
        $this->assertCount(1, $this->json($response)['data']['documents']);
    }

    public function test_it_rejects_files_that_are_not_real_pdf_or_zip(): void
    {
        $response = $this->update($this->tenderPayload(), [
            ['title' => 'Malicioso', 'description' => 'x', 'file' => $this->upload('MZ fake', 'virus.pdf', 'application/pdf')],
            ['title' => 'Imagen', 'description' => 'x', 'file' => $this->upload('GIF89a', 'foto.gif', 'image/gif')],
        ]);

        $this->assertSame(422, $response->status());
        $errors = $this->json($response)['errors'];
        $this->assertSame(['El archivo documento debe ser de tipo PDF, ZIP.'], $errors['documents.0.file']);
        $this->assertArrayHasKey('documents.1.file', $errors);
        $this->assertSame(0, TenderDocument::query()->count());
    }

    public function test_it_validates_document_fields(): void
    {
        $response = $this->update($this->tenderPayload(), [
            ['title' => '', 'description' => str_repeat('a', 201), 'file' => $this->pdfUpload()],
        ]);

        $errors = $this->json($response)['errors'];
        $this->assertSame(['El campo título del documento es obligatorio.'], $errors['documents.0.title']);
        $this->assertSame(
            ['El campo descripción del documento no debe superar 200 caracteres.'],
            $errors['documents.0.description'],
        );
    }

    public function test_it_applies_the_same_rules_as_creation(): void
    {
        $response = $this->update($this->tenderPayload(['closing_date' => '2026-10-01']), [
            ['title' => 'Pliego', 'description' => 'Pliego', 'file' => $this->pdfUpload()],
        ]);

        $this->assertSame(422, $response->status());
        $this->assertArrayHasKey('closing_date', $this->json($response)['errors']);
        $this->assertSame('Compra de portátiles', Tender::query()->findOrFail($this->tenderId)->subject);
    }

    public function test_it_returns_404_when_updating_unknown_tender(): void
    {
        $this->tenderId = 999999;

        $this->assertSame(404, $this->update($this->tenderPayload(), [])->status());
    }

    /**
     * Simulates the multipart form: fields + documents[i][title|description] + documents[i][file].
     *
     * @param array<string, mixed> $fields
     * @param list<array{title: string, description: string, file: \App\Core\Http\UploadedFile}> $documents
     */
    private function update(array $fields, array $documents): \App\Core\Http\Response
    {
        $body = $fields + ['_method' => 'PUT'];
        $files = [];
        foreach ($documents as $i => $document) {
            $body['documents'][$i] = ['title' => $document['title'], 'description' => $document['description']];
            $files['documents'][$i] = ['file' => $document['file']];
        }

        return $this->call('POST', "/api/tenders/{$this->tenderId}", $body, $files);
    }

    private function documentsPath(): string
    {
        return (string) getenv('DOCUMENTS_PATH');
    }
}
