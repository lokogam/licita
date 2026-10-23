<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Support\Clock;
use JsonException;

/**
 * Minimal JSON Web Token implementation (RFC 7519) restricted to HS256.
 * Only HS256 is accepted, which blocks "alg: none" and algorithm-confusion attacks.
 */
final class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly Clock $clock,
        private readonly int $ttlSeconds = 900,
    ) {
    }

    public function ttl(): int
    {
        return $this->ttlSeconds;
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function issue(array $claims): string
    {
        $now = $this->clock->now()->getTimestamp();
        $header = ['alg' => self::ALGORITHM, 'typ' => 'JWT'];
        $payload = $claims + ['iat' => $now, 'exp' => $now + $this->ttlSeconds, 'jti' => bin2hex(random_bytes(8))];

        $unsigned = self::encode((string) json_encode($header)) . '.' . self::encode((string) json_encode($payload));

        return $unsigned . '.' . $this->sign($unsigned);
    }

    /**
     * @return array<string, mixed>
     * @throws AuthenticationException when the token is malformed, tampered or expired
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new AuthenticationException();
        }
        [$header, $payload, $signature] = $parts;

        try {
            $headerData = json_decode(self::decodeSegment($header), true, flags: JSON_THROW_ON_ERROR);
            $claims = json_decode(self::decodeSegment($payload), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new AuthenticationException();
        }

        if (!is_array($headerData) || ($headerData['alg'] ?? null) !== self::ALGORITHM || !is_array($claims)) {
            throw new AuthenticationException();
        }

        if (!hash_equals($this->sign("{$header}.{$payload}"), $signature)) {
            throw new AuthenticationException();
        }

        if (!is_int($claims['exp'] ?? null) || $claims['exp'] <= $this->clock->now()->getTimestamp()) {
            throw new AuthenticationException();
        }

        return $claims;
    }

    private function sign(string $unsigned): string
    {
        return self::encode(hash_hmac('sha256', $unsigned, 'jwt|' . $this->secret, true));
    }

    private static function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function decodeSegment(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
