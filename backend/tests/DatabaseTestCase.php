<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Kernel;
use Illuminate\Database\Capsule\Manager as Capsule;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that hit MySQL.
 *
 * The schema from bd/01_schema.sql is loaded once per run and every test
 * runs inside a transaction that is rolled back afterwards.
 */
abstract class DatabaseTestCase extends TestCase
{
    private static bool $schemaLoaded = false;

    protected Kernel $kernel;

    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        self::loadSchemaOnce();

        $this->kernel = require __DIR__ . '/../bootstrap/app.php';
        $this->container = $container; // defined by bootstrap/app.php

        Capsule::connection()->beginTransaction();
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
     */
    protected function call(string $method, string $uri, array $body = [], array $files = []): Response
    {
        return $this->kernel->handle(Request::create($method, $uri, body: $body, files: $files));
    }

    /**
     * @return array<string, mixed>
     */
    protected function json(Response $response): array
    {
        return json_decode($response->content(), true, flags: JSON_THROW_ON_ERROR);
    }

    private static function loadSchemaOnce(): void
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
        $pdo->exec("USE `{$database}`");
        $pdo->exec((string) file_get_contents(__DIR__ . '/../../bd/01_schema.sql'));

        self::$schemaLoaded = true;
    }
}
