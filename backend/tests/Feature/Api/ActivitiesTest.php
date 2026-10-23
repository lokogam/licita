<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\DatabaseTestCase;
use Tests\Feature\Concerns\CreatesTenders;

final class ActivitiesTest extends DatabaseTestCase
{
    use CreatesTenders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activity(43211503, 'Computadores portátiles');
        $this->activity(43211507, 'Computadores de escritorio');
        $this->activity(44121701, 'Bolígrafos');
    }

    public function test_it_searches_activities_by_product_name(): void
    {
        $body = $this->json($this->call('GET', '/api/activities?search=computadores'));

        $this->assertSame(
            ['Computadores de escritorio', 'Computadores portátiles'],
            array_column($body['data'], 'product'),
        );
        $this->assertSame(
            ['id', 'product_code', 'product', 'class_name', 'family', 'segment'],
            array_keys($body['data'][0]),
        );
    }

    public function test_it_searches_activities_by_product_code_prefix(): void
    {
        $body = $this->json($this->call('GET', '/api/activities?search=4412'));

        $this->assertSame(['Bolígrafos'], array_column($body['data'], 'product'));
    }

    public function test_it_limits_results(): void
    {
        $body = $this->json($this->call('GET', '/api/activities?search=computadores&limit=1'));

        $this->assertCount(1, $body['data']);
    }

    public function test_short_searches_return_no_results(): void
    {
        $body = $this->json($this->call('GET', '/api/activities?search=c'));

        $this->assertSame([], $body['data']);
    }

    public function test_without_search_it_returns_the_first_activities_alphabetically(): void
    {
        $body = $this->json($this->call('GET', '/api/activities?limit=2'));

        $this->assertSame(
            ['Bolígrafos', 'Computadores de escritorio'],
            array_column($body['data'], 'product'),
        );
    }

    public function test_it_shows_a_single_activity(): void
    {
        $id = $this->json($this->call('GET', '/api/activities?search=Bolígrafos'))['data'][0]['id'];

        $response = $this->call('GET', "/api/activities/{$id}");

        $this->assertSame(200, $response->status());
        $this->assertSame(44121701, $this->json($response)['data']['product_code']);
    }
}
