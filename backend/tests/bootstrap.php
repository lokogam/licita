<?php

/**
 * PHPUnit bootstrap.
 *
 * Feature tests always run against a dedicated database (never the application one)
 * using the MySQL root account so the schema can be recreated.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$defaults = [
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3307',
];

$forced = [
    'DB_DATABASE' => getenv('DB_TEST_DATABASE') ?: 'suplos_tenders_test',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => getenv('DB_ROOT_PASSWORD') ?: 'root',
    'DOCUMENTS_PATH' => sys_get_temp_dir() . '/tenders-test-documents',
];

foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        $forced[$key] = $value;
    }
}

foreach ($forced as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
