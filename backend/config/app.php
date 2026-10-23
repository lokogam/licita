<?php

declare(strict_types=1);

use App\Core\Support\Env;

return [
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG'),
    'url' => Env::get('APP_URL', 'http://localhost:8080'),
    'timezone' => Env::get('APP_TIMEZONE', 'America/Bogota'),
    'cors_origin' => Env::get('CORS_ALLOWED_ORIGIN', '*'),
    'storage_path' => dirname(__DIR__) . '/storage',
];
