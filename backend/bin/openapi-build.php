<?php

/**
 * Pre-generates the OpenAPI document into storage/cache/openapi.json.
 *
 * Usage: php bin/openapi-build.php
 */

declare(strict_types=1);

use App\Services\OpenApiSpec;

require __DIR__ . '/../vendor/autoload.php';

$target = __DIR__ . '/../storage/cache/openapi.json';
(new OpenApiSpec($target, debug: false))->build();

fwrite(STDOUT, "OpenAPI document written to {$target}\n");
