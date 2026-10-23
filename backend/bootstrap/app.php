<?php

/**
 * Builds the application: environment, configuration, ORM, container and routes.
 *
 * Returns the HTTP kernel ready to handle requests.
 */

declare(strict_types=1);

use App\Core\Container;
use App\Core\ExceptionHandler;
use App\Core\Kernel;
use App\Core\Routing\Router;
use App\Services\DocumentStorage;
use App\Services\OpenApiSpec;
use App\Support\Clock;
use App\Support\SystemClock;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;

$basePath = dirname(__DIR__);

require_once $basePath . '/vendor/autoload.php';

Dotenv::createImmutable($basePath)->safeLoad();

/** @var array{env: string, debug: bool, url: string, timezone: string, cors_origin: string, storage_path: string, documents_path: string} $config */
$config = require $basePath . '/config/app.php';

date_default_timezone_set($config['timezone']);

$container = new Container();
$container->instance(Container::class, $container);
$container->instance('config.app', $config);

$capsule = new Capsule();
$capsule->addConnection(require $basePath . '/config/database.php');
$capsule->setAsGlobal();
$capsule->bootEloquent();
$container->instance(Capsule::class, $capsule);
$container->instance(ConnectionInterface::class, $capsule->getConnection());

$container->singleton(Clock::class, SystemClock::class);
$container->singleton(DocumentStorage::class, fn (): DocumentStorage => new DocumentStorage($config['documents_path']));
$container->singleton(
    OpenApiSpec::class,
    fn (): OpenApiSpec => new OpenApiSpec($config['storage_path'] . '/cache/openapi.json', $config['debug']),
);

$router = new Router($container);
require $basePath . '/routes/api.php';

return new Kernel(
    $router,
    new ExceptionHandler($config['debug'], $config['storage_path'] . '/logs/app.log'),
    $config['cors_origin'],
);
