<?php

/**
 * Front controller: every /api request is routed through this file.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode(['status' => 'ok']);
