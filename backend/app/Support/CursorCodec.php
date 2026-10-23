<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Exceptions\HttpException;

/**
 * Encodes pagination positions as opaque, HMAC-signed, URL-safe tokens
 * so clients cannot forge or alter them.
 */
final class CursorCodec
{
    public function __construct(private readonly string $key)
    {
    }

    /**
     * @param array<string, int|string> $position
     */
    public function encode(array $position): string
    {
        $payload = self::base64UrlEncode(json_encode($position, JSON_THROW_ON_ERROR));

        return $payload . '.' . $this->sign($payload);
    }

    /**
     * @return array<string, mixed>
     * @throws HttpException 400 when the token is malformed or its signature does not match
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2 || !hash_equals($this->sign($parts[0]), $parts[1])) {
            throw self::invalid();
        }

        $position = json_decode(self::base64UrlDecode($parts[0]), true);
        if (!is_array($position)) {
            throw self::invalid();
        }

        return $position;
    }

    private function sign(string $payload): string
    {
        return self::base64UrlEncode(hash_hmac('sha256', $payload, $this->key, true));
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }

    private static function invalid(): HttpException
    {
        return new HttpException(400, 'El cursor de paginación no es válido.');
    }
}
