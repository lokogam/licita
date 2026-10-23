<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Support\Clock;
use App\Support\FrozenClock;
use Tests\DatabaseTestCase;
use Tests\Feature\Concerns\CreatesTenders;

final class CursorPaginationTest extends DatabaseTestCase
{
    use CreatesTenders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->instance(Clock::class, new FrozenClock('2026-11-05 10:00:00'));

        for ($i = 1; $i <= 5; $i++) {
            $this->create("Licitación {$i}");
        }
    }

    public function test_it_walks_every_tender_newest_first_with_cursors(): void
    {
        $first = $this->json($this->call('GET', '/api/tenders?cursor=&per_page=2'));
        $this->assertSame(['Licitación 5', 'Licitación 4'], array_column($first['data'], 'subject'));
        $this->assertSame(2, $first['meta']['per_page']);
        $this->assertNotNull($first['meta']['next_cursor']);

        $second = $this->json($this->call('GET', '/api/tenders?per_page=2&cursor=' . $first['meta']['next_cursor']));
        $this->assertSame(['Licitación 3', 'Licitación 2'], array_column($second['data'], 'subject'));

        $last = $this->json($this->call('GET', '/api/tenders?per_page=2&cursor=' . $second['meta']['next_cursor']));
        $this->assertSame(['Licitación 1'], array_column($last['data'], 'subject'));
        $this->assertNull($last['meta']['next_cursor']);
    }

    public function test_new_tenders_do_not_shift_the_following_pages(): void
    {
        $first = $this->json($this->call('GET', '/api/tenders?cursor=&per_page=2'));

        $this->create('Licitación nueva');

        $second = $this->json($this->call('GET', '/api/tenders?per_page=2&cursor=' . $first['meta']['next_cursor']));
        $this->assertSame(['Licitación 3', 'Licitación 2'], array_column($second['data'], 'subject'));
    }

    public function test_cursors_combine_with_filters(): void
    {
        $page = $this->json($this->call('GET', '/api/tenders?cursor=&per_page=10&subject=Licitación 1'));

        $this->assertSame(['Licitación 1'], array_column($page['data'], 'subject'));
        $this->assertNull($page['meta']['next_cursor']);
    }

    public function test_it_rejects_tampered_cursors(): void
    {
        $response = $this->call('GET', '/api/tenders?cursor=eyJpZCI6OTl9.forged');

        $this->assertSame(400, $response->status());
        $this->assertSame('El cursor de paginación no es válido.', $this->json($response)['message']);
    }

    private function create(string $subject): void
    {
        $this->call('POST', '/api/tenders', $this->tenderPayload(['subject' => $subject]));
    }
}
