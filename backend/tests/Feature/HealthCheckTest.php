<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Http\Request;
use App\Core\Kernel;
use PHPUnit\Framework\TestCase;

final class HealthCheckTest extends TestCase
{
    public function test_the_bootstrapped_application_answers_the_health_endpoint(): void
    {
        /** @var Kernel $kernel */
        $kernel = require __DIR__ . '/../../bootstrap/app.php';

        $response = $kernel->handle(Request::create('GET', '/api/health'));

        $this->assertSame(200, $response->status());
        $this->assertSame(['status' => 'ok'], json_decode($response->content(), true));
    }

    public function test_unknown_api_routes_return_json_404(): void
    {
        /** @var Kernel $kernel */
        $kernel = require __DIR__ . '/../../bootstrap/app.php';

        $response = $kernel->handle(Request::create('GET', '/api/unknown'));

        $this->assertSame(404, $response->status());
    }
}
