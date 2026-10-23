<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * The refresh token travels only in this cookie: HttpOnly (no JavaScript access),
 * SameSite=Strict (no cross-site requests) and scoped to the auth endpoints.
 */
final class RefreshCookie
{
    public const NAME = 'refresh_token';

    private const PATH = '/api/auth';

    public function __construct(
        private readonly int $maxAgeSeconds,
        private readonly bool $secure = true,
    ) {
    }

    public function read(Request $request): ?string
    {
        $value = $request->cookie(self::NAME);

        return $value !== null && $value !== '' ? $value : null;
    }

    public function attach(Response $response, string $token): Response
    {
        return $response->withHeaders(['Set-Cookie' => $this->header($token, $this->maxAgeSeconds)]);
    }

    public function forget(Response $response): Response
    {
        return $response->withHeaders(['Set-Cookie' => $this->header('', 0)]);
    }

    private function header(string $value, int $maxAge): string
    {
        $parts = [
            self::NAME . '=' . rawurlencode($value),
            'Path=' . self::PATH,
            'Max-Age=' . $maxAge,
            'HttpOnly',
            'SameSite=Strict',
        ];

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        return implode('; ', $parts);
    }
}
