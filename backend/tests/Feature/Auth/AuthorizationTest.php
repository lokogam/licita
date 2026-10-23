<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Support\Clock;
use App\Support\FrozenClock;
use Tests\DatabaseTestCase;
use Tests\Feature\Concerns\CreatesTenders;

final class AuthorizationTest extends DatabaseTestCase
{
    use CreatesTenders;

    private int $tenderId;

    private int $documentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->instance(Clock::class, new FrozenClock('2026-10-20 10:00:00'));

        // Seed as admin (default), then each test switches role.
        $this->tenderId = $this->json($this->call('POST', '/api/tenders', $this->tenderPayload()))['data']['id'];
        $response = $this->call(
            'POST',
            "/api/tenders/{$this->tenderId}",
            $this->tenderPayload() + ['_method' => 'PUT', 'documents' => [
                ['title' => 'Pliego', 'description' => 'x'],
                ['title' => 'Anexo', 'description' => 'y'],
            ]],
            ['documents' => [['file' => $this->pdfUpload()], ['file' => $this->pdfUpload('anexo.pdf')]]],
        );
        $this->documentId = $this->json($response)['data']['documents'][0]['id'];
    }

    public function test_guests_only_reach_public_endpoints(): void
    {
        $this->actingAsGuest();

        $this->assertSame(200, $this->call('GET', '/api/health')->status());
        $this->assertSame(200, $this->call('GET', '/api/openapi.json')->status());

        $response = $this->call('GET', '/api/tenders');
        $this->assertSame(401, $response->status());
        $this->assertSame('Bearer', $response->headers()['WWW-Authenticate']);
        $this->assertSame(401, $this->call('GET', '/api/activities')->status());
    }

    public function test_invalid_tokens_are_rejected(): void
    {
        $this->actingAsGuest();

        $response = $this->call('GET', '/api/tenders', headers: ['Authorization' => 'Bearer forged.token.value']);

        $this->assertSame(401, $response->status());
    }

    public function test_viewers_can_only_read(): void
    {
        $this->actingAs(Role::Viewer);

        $this->assertSame(200, $this->call('GET', '/api/tenders')->status());
        $this->assertSame(200, $this->call('GET', "/api/tenders/{$this->tenderId}")->status());
        $this->assertSame(200, $this->call('GET', '/api/tenders/export')->status());
        $this->assertSame(200, $this->call('GET', '/api/activities?search=computadores')->status());
        $this->assertSame(
            200,
            $this->call('GET', "/api/tenders/{$this->tenderId}/documents/{$this->documentId}/download")->status(),
        );

        $forbidden = $this->call('POST', '/api/tenders', $this->tenderPayload());
        $this->assertSame(403, $forbidden->status());
        $this->assertSame('No tiene permisos para realizar esta acción.', $this->json($forbidden)['message']);
        $this->assertSame(
            403,
            $this->call('POST', "/api/tenders/{$this->tenderId}", $this->tenderPayload() + ['_method' => 'PUT'])->status(),
        );
        $this->assertSame(
            403,
            $this->call('DELETE', "/api/tenders/{$this->tenderId}/documents/{$this->documentId}")->status(),
        );
        $this->assertSame(403, $this->call('GET', '/api/users')->status());
    }

    public function test_editors_manage_tenders_and_documents_but_not_users(): void
    {
        $this->actingAs(Role::Editor);

        $this->assertSame(201, $this->call('POST', '/api/tenders', $this->tenderPayload())->status());
        $this->assertSame(
            200,
            $this->call('DELETE', "/api/tenders/{$this->tenderId}/documents/{$this->documentId}")->status(),
        );
        $this->assertSame(403, $this->call('GET', '/api/users')->status());
    }

    public function test_tokens_of_deleted_users_stop_working(): void
    {
        $viewer = $this->actingAs(Role::Viewer);
        $viewer->delete();

        $this->assertSame(401, $this->call('GET', '/api/tenders')->status());
    }
}
