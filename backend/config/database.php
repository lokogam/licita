<?php

declare(strict_types=1);

use App\Core\Support\DatabaseSsl;
use App\Core\Support\Env;

return [
    'driver' => 'mysql',
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', 'licita'),
    'username' => Env::get('DB_USERNAME', 'licita'),
    'password' => Env::get('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    // Managed MySQL (Aiven, etc.): CA certificate in PEM format enables TLS.
    'options' => DatabaseSsl::options(Env::get('DB_SSL_CA'), sys_get_temp_dir()),
];
