<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Services\Security\ScannerUnavailableException;
use App\Services\Security\ScanResult;
use App\Services\Security\VirusScanner;
use App\Support\Clock;
use App\Support\FrozenClock;
use Tests\DatabaseTestCase;
use Tests\Feature\Concerns\CreatesTenders;

final class AntivirusValidationTest extends DatabaseTestCase
{
    use CreatesTenders;

    private int $tenderId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->instance(Clock::class, new FrozenClock('2026-10-20 10:00:00'));
        $this->tenderId = $this->json($this->call('POST', '/api/tenders', $this->tenderPayload()))['data']['id'];
    }

    public function test_infected_uploads_are_rejected(): void
    {
        $this->useScanner(fn (): ScanResult => ScanResult::infected('Eicar-Test-Signature'));

        $response = $this->uploadDocument();

        $this->assertSame(422, $response->status());
        $this->assertSame(
            ['El archivo documento fue rechazado por el antivirus.'],
            $this->json($response)['errors']['documents.0.file'],
        );
    }

    public function test_clean_uploads_are_accepted(): void
    {
        $this->useScanner(fn (): ScanResult => ScanResult::clean());

        $this->assertSame(200, $this->uploadDocument()->status());
    }

    public function test_uploads_fail_closed_when_the_antivirus_is_down(): void
    {
        $this->useScanner(function (): never {
            throw new ScannerUnavailableException('clamd is not reachable');
        });

        $response = $this->uploadDocument();

        $this->assertSame(503, $response->status());
        $this->assertSame(
            'El servicio antivirus no está disponible. Intente nuevamente en unos minutos.',
            $this->json($response)['message'],
        );
    }

    /**
     * @param callable(string): ScanResult $verdict
     */
    private function useScanner(callable $verdict): void
    {
        $this->container->instance(VirusScanner::class, new class ($verdict) implements VirusScanner {
            /** @var callable(string): ScanResult */
            private $verdict;

            public function __construct(callable $verdict)
            {
                $this->verdict = $verdict;
            }

            public function scan(string $path): ScanResult
            {
                return ($this->verdict)($path);
            }
        });
    }

    private function uploadDocument(): \App\Core\Http\Response
    {
        return $this->call(
            'POST',
            "/api/tenders/{$this->tenderId}",
            $this->tenderPayload() + ['_method' => 'PUT', 'documents' => [['title' => 'Pliego', 'description' => 'x']]],
            ['documents' => [['file' => $this->pdfUpload()]]],
        );
    }
}
