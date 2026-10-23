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
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\Authorize;
use App\Http\RefreshCookie;
use App\Services\Auth\JwtService;
use App\Services\Auth\RefreshTokenService;
use App\Services\DocumentStorage;
use App\Services\OpenApiSpec;
use App\Services\Security\ClamAvScanner;
use App\Services\Security\NullScanner;
use App\Services\Security\VirusScanner;
use App\Support\Clock;
use App\Support\CursorCodec;
use App\Support\SystemClock;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;

$basePath = dirname(__DIR__);

require_once $basePath . '/vendor/autoload.php';

Dotenv::createImmutable($basePath)->safeLoad();

/** @var array{env: string, debug: bool, key: string, url: string, timezone: string, cors_origin: string, storage_path: string, documents_path: string, openapi_cache: string, auth: array{access_ttl: int, refresh_ttl_days: int, cookie_secure: bool, trust_proxy: bool}, antivirus: array{enabled: bool, host: string, port: int}} $config */
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
$container->singleton(
    VirusScanner::class,
    fn (): VirusScanner => $config['antivirus']['enabled']
        ? ClamAvScanner::tcp($config['antivirus']['host'], $config['antivirus']['port'])
        : new NullScanner(),
);
$container->singleton(CursorCodec::class, fn (): CursorCodec => new CursorCodec($config['key']));
$container->singleton(DocumentStorage::class, fn (): DocumentStorage => new DocumentStorage($config['documents_path']));
$container->singleton(
    OpenApiSpec::class,
    fn (): OpenApiSpec => new OpenApiSpec($config['openapi_cache'], $config['debug']),
);

// Not shared: they always use the current Clock binding (tests freeze it).
$container->bind(
    JwtService::class,
    fn (Container $c): JwtService => new JwtService(
        $config['key'],
        $c->make(Clock::class),
        $config['auth']['access_ttl'],
    ),
);
$container->bind(
    RefreshTokenService::class,
    fn (Container $c): RefreshTokenService => new RefreshTokenService(
        $c->make(Clock::class),
        $config['auth']['refresh_ttl_days'],
    ),
);
$container->singleton(
    RefreshCookie::class,
    fn (): RefreshCookie => new RefreshCookie(
        $config['auth']['refresh_ttl_days'] * 86400,
        $config['auth']['cookie_secure'],
    ),
);

$router = new Router($container);
$router->aliasMiddleware('auth', Authenticate::class);
$router->aliasMiddleware('can', Authorize::class);
require $basePath . '/routes/api.php';

return new Kernel(
    $router,
    new ExceptionHandler($config['debug'], $config['storage_path'] . '/logs/app.log'),
    $config['cors_origin'],
    $config['auth']['trust_proxy'],
);
