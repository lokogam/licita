<?php

/**
 * Front controller: every /api request is routed through this file.
 */

declare(strict_types=1);

use App\Core\Http\Request;
use App\Core\Kernel;

/** @var Kernel $kernel */
$kernel = require __DIR__ . '/../bootstrap/app.php';

$kernel->handle(Request::capture($kernel->trustsProxy()))->send();
