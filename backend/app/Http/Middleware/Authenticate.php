<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\Middleware;
use App\Models\User;
use App\Services\Auth\AuthenticationException;
use App\Services\Auth\JwtService;
use Closure;

/**
 * Requires a valid access token and attaches the current user to the request ("user" attribute).
 *
 * The user is read from the database on every request, so deleted users and
 * role changes take effect immediately instead of when the token expires.
 */
final class Authenticate implements Middleware
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next, string ...$parameters): Response
    {
        $token = $request->bearerToken() ?? throw new AuthenticationException();
        $claims = $this->jwt->decode($token);

        $userId = $claims['sub'] ?? null;
        $user = is_int($userId) || is_string($userId) ? User::query()->find((int) $userId) : null;

        if (!$user instanceof User) {
            throw new AuthenticationException();
        }

        return $next($request->withAttribute('user', $user));
    }
}
