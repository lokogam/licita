<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Support\Clock;
use App\Support\FrozenClock;
use Tests\DatabaseTestCase;
use Tests\Feature\Concerns\CreatesTenders;

final class ListAndShowTendersTest extends DatabaseTestCase
{
    use CreatesTenders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->instance(Clock::class, new FrozenClock('2026-11-05 10:00:00'));
    }

    public function test_it_lists_tenders_newest_first_with_pagination_meta(): void
    {
        $this->seedTenders(3);

        $response = $this->call('GET', '/api/tenders?per_page=2');

        $this->assertSame(200, $response->status());
        $body = $this->json($response);
        $this->assertSame(['O-0003-26', 'O-0002-26'], array_column($body['data'], 'consecutive'));
        $this->assertSame(
            ['current_page' => 1, 'per_page' => 2, 'total' => 3, 'last_page' => 2, 'from' => 1, 'to' => 2],
            $body['meta'],
        );
        $this->assertSame(
            ['id', 'consecutive', 'subject', 'description', 'start_date', 'closing_date', 'status'],
            array_keys($body['data'][0]),
        );
    }

    public function test_it_returns_the_requested_page(): void
    {
        $this->seedTenders(3);

        $body = $this->json($this->call('GET', '/api/tenders?per_page=2&page=2'));

        $this->assertSame(['O-0001-26'], array_column($body['data'], 'consecutive'));
    }

    public function test_status_in_list_is_computed_at_request_time(): void
    {
        $this->seedTenders(1);

        $body = $this->json($this->call('GET', '/api/tenders'));

        $this->assertSame(['value' => 'open', 'label' => 'Abierta'], $body['data'][0]['status']);
    }

    public function test_it_filters_by_consecutive_subject_and_description(): void
    {
        $activity = $this->activity();
        $this->call('POST', '/api/tenders', $this->tenderPayload([
            'activity_id' => $activity->id,
            'subject' => 'Servicio de vigilancia',
            'description' => 'Seguridad privada para sedes',
        ]));
        $this->call('POST', '/api/tenders', $this->tenderPayload([
            'activity_id' => $activity->id,
            'subject' => 'Compra de papelería',
            'description' => 'Resmas y útiles de oficina',
        ]));

        $this->assertSame(['O-0001-26'], $this->consecutives('/api/tenders?subject=vigilancia'));
        $this->assertSame(['O-0002-26'], $this->consecutives('/api/tenders?description=oficina'));
        $this->assertSame(['O-0002-26'], $this->consecutives('/api/tenders?consecutive=0002'));
        $this->assertSame([], $this->consecutives('/api/tenders?subject=vigilancia&description=oficina'));
    }

    public function test_filters_treat_sql_wildcards_literally(): void
    {
        $this->seedTenders(2);

        $this->assertSame([], $this->consecutives('/api/tenders?subject=%25'));
        $this->assertSame([], $this->consecutives('/api/tenders?subject=_'));
    }

    public function test_per_page_is_capped(): void
    {
        $this->seedTenders(1);

        $body = $this->json($this->call('GET', '/api/tenders?per_page=5000&page=-3'));

        $this->assertSame(100, $body['meta']['per_page']);
        $this->assertSame(1, $body['meta']['current_page']);
    }

    public function test_it_shows_the_tender_detail(): void
    {
        $this->seedTenders(1);
        $id = $this->json($this->call('GET', '/api/tenders'))['data'][0]['id'];

        $response = $this->call('GET', "/api/tenders/{$id}");

        $this->assertSame(200, $response->status());
        $data = $this->json($response)['data'];
        $this->assertSame('O-0001-26', $data['consecutive']);
        $this->assertSame('COP', $data['currency']);
        $this->assertSame('17:30', $data['closing_time']);
        $this->assertSame(43211503, $data['activity']['product_code']);
        $this->assertArrayHasKey('created_at', $data);
    }

    public function test_it_returns_404_for_unknown_tenders(): void
    {
        $response = $this->call('GET', '/api/tenders/999999');

        $this->assertSame(404, $response->status());
        $this->assertSame('La licitación solicitada no existe.', $this->json($response)['message']);
    }

    private function seedTenders(int $count): void
    {
        $activity = $this->activity();
        for ($i = 0; $i < $count; $i++) {
            $this->call('POST', '/api/tenders', $this->tenderPayload(['activity_id' => $activity->id]));
        }
    }

    /**
     * @return list<string>
     */
    private function consecutives(string $uri): array
    {
        return array_column($this->json($this->call('GET', $uri))['data'], 'consecutive');
    }
}
