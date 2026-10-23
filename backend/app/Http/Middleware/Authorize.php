<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Exceptions\HttpException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\Middleware;
use App\Models\User;
use App\Services\Auth\AuthenticationException;
use Closure;

/**
 * Requires the authenticated user's role to grant the given ability ("can:tenders.create").
 * Must run after Authenticate.
 */
final class Authorize implements Middleware
{
    public function handle(Request $request, Closure $next, string ...$parameters): Response
    {
        $user = $request->attribute('user');
        if (!$user instanceof User) {
            throw new AuthenticationException();
        }

        foreach ($parameters as $ability) {
            if (!$user->role->can($ability)) {
                throw new HttpException(403, 'No tiene permisos para realizar esta acción.');
            }
        }

        return $next($request);
    }
}
