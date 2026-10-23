<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Core\Http\Response;
use App\Support\Clock;
use App\Support\FrozenClock;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\DatabaseTestCase;
use Tests\Feature\Concerns\CreatesTenders;

final class ExportTendersTest extends DatabaseTestCase
{
    use CreatesTenders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->instance(Clock::class, new FrozenClock('2026-11-05 10:00:00'));
    }

    public function test_it_exports_every_tender_ignoring_pagination(): void
    {
        $activity = $this->activity();
        for ($i = 1; $i <= 12; $i++) {
            $this->call('POST', '/api/tenders', $this->tenderPayload([
                'activity_id' => $activity->id,
                'subject' => "Licitación {$i}",
            ]));
        }

        $response = $this->call('GET', '/api/tenders/export?page=1&per_page=5');

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers()['Content-Type'],
        );
        $this->assertMatchesRegularExpression(
            '/attachment; filename="licitaciones-\d{8}-\d{6}\.xlsx"/',
            $response->headers()['Content-Disposition'],
        );

        $rows = $this->rows($response);
        $this->assertSame(
            ['Consecutivo', 'Objeto', 'Descripción', 'Fecha de inicio', 'Fecha de cierre', 'Estado'],
            $rows[0],
        );
        $this->assertCount(13, $rows);
        $this->assertSame(
            ['O-0012-26', 'Licitación 12', $this->tenderPayload()['description'], '2026-11-01 08:00', '2026-11-15 17:30', 'Abierta'],
            $rows[1],
        );
    }

    public function test_it_applies_the_same_filters_as_the_list(): void
    {
        $activity = $this->activity();
        $this->call('POST', '/api/tenders', $this->tenderPayload(['activity_id' => $activity->id, 'subject' => 'Vigilancia']));
        $this->call('POST', '/api/tenders', $this->tenderPayload(['activity_id' => $activity->id, 'subject' => 'Papelería']));

        $rows = $this->rows($this->call('GET', '/api/tenders/export?subject=vigil'));

        $this->assertCount(2, $rows);
        $this->assertSame('Vigilancia', $rows[1][1]);
    }

    public function test_it_exports_only_headers_when_nothing_matches(): void
    {
        $rows = $this->rows($this->call('GET', '/api/tenders/export?subject=nada'));

        $this->assertCount(1, $rows);
    }

    /**
     * @return list<list<mixed>>
     */
    private function rows(Response $response): array
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->content());

        $sheet = IOFactory::load($path)->getActiveSheet();

        return array_values(array_map('array_values', $sheet->toArray(null, false, false, false)));
    }
}
