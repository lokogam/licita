<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Kernel;
use App\Database\Migrator;
use App\Enums\Role;
use App\Models\User;
use App\Services\Auth\JwtService;
use Illuminate\Database\Capsule\Manager as Capsule;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that hit MySQL.
 *
 * The schema is built from the migrations once per run and every test
 * runs inside a transaction that is rolled back afterwards.
 * Requests are authenticated as an administrator unless a test chooses otherwise.
 */
abstract class DatabaseTestCase extends TestCase
{
    private static bool $schemaLoaded = false;

    protected Kernel $kernel;

    protected Container $container;

    protected ?string $accessToken = null;

    protected ?User $user = null;

    protected function setUp(): void
    {
        parent::setUp();

        self::ensureDatabaseExists();

        $this->kernel = require __DIR__ . '/../bootstrap/app.php';
        $this->container = $container; // defined by bootstrap/app.php
        self::migrateOnce();

        Capsule::connection()->beginTransaction();

        $this->actingAs(Role::Admin);
    }

    /**
     * Authenticates the following requests as a new user with the given role.
     */
    protected function actingAs(Role $role): User
    {
        $this->user = User::register(
            ucfirst($role->value) . ' Test',
            $role->value . '-' . bin2hex(random_bytes(4)) . '@test.local',
            'Clave-Segura-2026',
            $role,
        );
        // Far-future expiry: many tests freeze the clock at arbitrary dates.
        $this->accessToken = $this->container->make(JwtService::class)->issue([
            'sub' => (string) $this->user->id,
            'exp' => 4102444800,
        ]);

        return $this->user;
    }

    protected function actingAsGuest(): void
    {
        $this->user = null;
        $this->accessToken = null;
    }

    protected function tearDown(): void
    {
        Capsule::connection()->rollBack();

        parent::tearDown();
    }

    /**
     * Sends a request through the full HTTP kernel.
     *
     * @param array<string, mixed> $body
     * @param array<string, mixed> $files
     * @param array<string, string> $cookies
     * @param array<string, string> $headers
     */
    protected function call(
        string $method,
        string $uri,
        array $body = [],
        array $files = [],
        array $cookies = [],
        array $headers = [],
    ): Response {
        if ($this->accessToken !== null) {
            $headers += ['Authorization' => 'Bearer ' . $this->accessToken];
        }

        return $this->kernel->handle(
            Request::create($method, $uri, body: $body, files: $files, headers: $headers, cookies: $cookies),
        );
    }

    /**
     * Value of a cookie set by the response (null when absent).
     */
    protected function cookieFrom(Response $response, string $name): ?string
    {
        $header = $response->headers()['Set-Cookie'] ?? '';

        return preg_match('/(?:^|;\s*)' . preg_quote($name, '/') . '=([^;]*)/', $header, $m) === 1 ? $m[1] : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function json(Response $response): array
    {
        return json_decode($response->content(), true, flags: JSON_THROW_ON_ERROR);
    }

    private static function ensureDatabaseExists(): void
    {
        if (self::$schemaLoaded) {
            return;
        }

        $database = (string) getenv('DB_DATABASE');
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_PORT')),
            (string) getenv('DB_USERNAME'),
            (string) getenv('DB_PASSWORD'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * The test database is rebuilt from the versioned migrations once per run.
     */
    private static function migrateOnce(): void
    {
        if (self::$schemaLoaded) {
            return;
        }

        (new Migrator(Capsule::connection(), __DIR__ . '/../database/migrations'))->fresh();

        self::$schemaLoaded = true;
    }
}
