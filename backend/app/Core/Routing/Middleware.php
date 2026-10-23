<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;

/**
 * Code that runs around a route handler (authentication, authorization...).
 */
interface Middleware
{
    /**
     * @param Closure(Request): Response $next
     * @param string ...$parameters Values after ":" in the route definition, e.g. "can:tenders.create".
     */
    public function handle(Request $request, Closure $next, string ...$parameters): Response;
}
