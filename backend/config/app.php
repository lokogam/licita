<?php

declare(strict_types=1);

use App\Core\Support\Env;

return [
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG'),
    // Secret used to sign pagination cursors (set a random value per environment).
    'key' => Env::get('APP_KEY', 'local-development-key'),
    'url' => Env::get('APP_URL', 'http://localhost:8080'),
    'timezone' => Env::get('APP_TIMEZONE', 'America/Bogota'),
    'cors_origin' => Env::get('CORS_ALLOWED_ORIGIN', '*'),
    'storage_path' => dirname(__DIR__) . '/storage',
    'openapi_cache' => Env::get('OPENAPI_CACHE', dirname(__DIR__) . '/storage/cache/openapi.json'),
    'documents_path' => Env::get('DOCUMENTS_PATH', dirname(__DIR__) . '/storage/app/documents'),
];
