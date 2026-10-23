<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Support\Clock;
use App\Support\FrozenClock;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\DatabaseTestCase;
use Tests\Feature\Concerns\CreatesTenders;

final class StatusFilterTest extends DatabaseTestCase
{
    use CreatesTenders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->instance(Clock::class, new FrozenClock('2026-11-05 10:00:00'));

        $this->tender('Programada', '2026-11-10', '08:00', '2026-11-20', '17:00');
        $this->tender('Abierta', '2026-11-01', '08:00', '2026-11-15', '17:00');
        $this->tender('Cerrada', '2026-10-01', '08:00', '2026-10-20', '17:00');
        $this->tender('Abre justo ahora', '2026-11-05', '10:00', '2026-11-06', '10:00');
        $this->tender('Cierra justo ahora', '2026-11-01', '08:00', '2026-11-05', '10:00');
    }

    public function test_it_filters_scheduled_tenders(): void
    {
        $this->assertSame(['Programada'], $this->subjects('/api/tenders?status=scheduled'));
    }

    public function test_it_filters_open_tenders_including_those_starting_now(): void
    {
        $this->assertSame(['Abre justo ahora', 'Abierta'], $this->subjects('/api/tenders?status=open'));
    }

    public function test_it_filters_closed_tenders_including_those_closing_now(): void
    {
        $this->assertSame(['Cierra justo ahora', 'Cerrada'], $this->subjects('/api/tenders?status=closed'));
    }

    public function test_filtered_rows_report_the_matching_status(): void
    {
        $body = $this->json($this->call('GET', '/api/tenders?status=open'));

        $this->assertSame(['open', 'open'], array_column(array_column($body['data'], 'status'), 'value'));
        $this->assertSame(2, $body['meta']['total']);
    }

    public function test_it_rejects_unknown_status_values(): void
    {
        $response = $this->call('GET', '/api/tenders?status=archived');

        $this->assertSame(422, $response->status());
        $this->assertSame(
            ['El campo estado debe ser uno de: scheduled, open, closed.'],
            $this->json($response)['errors']['status'],
        );
    }

    public function test_the_excel_export_respects_the_status_filter(): void
    {
        $response = $this->call('GET', '/api/tenders/export?status=closed');

        $path = (string) tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->content());
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, false, false, false);

        $this->assertSame(['Cierra justo ahora', 'Cerrada'], array_column(array_slice($rows, 1), 1));
    }

    private function tender(string $subject, string $startDate, string $startTime, string $closingDate, string $closingTime): void
    {
        $response = $this->call('POST', '/api/tenders', $this->tenderPayload([
            'subject' => $subject,
            'start_date' => $startDate,
            'start_time' => $startTime,
            'closing_date' => $closingDate,
            'closing_time' => $closingTime,
        ]));

        $this->assertSame(201, $response->status(), $response->content());
    }

    /**
     * @return list<string>
     */
    private function subjects(string $uri): array
    {
        return array_column($this->json($this->call('GET', $uri))['data'], 'subject');
    }
}
