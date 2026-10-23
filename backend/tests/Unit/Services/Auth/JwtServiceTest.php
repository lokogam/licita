<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\AuthenticationException;
use App\Services\Auth\JwtService;
use App\Support\FrozenClock;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    private const SECRET = 'test-secret-key';

    public function test_it_issues_tokens_that_decode_to_the_claims(): void
    {
        $jwt = new JwtService(self::SECRET, new FrozenClock('2026-10-23 10:00:00'), ttlSeconds: 900);

        $token = $jwt->issue(['sub' => 7, 'role' => 'editor']);
        $claims = $jwt->decode($token);

        $this->assertSame(7, $claims['sub']);
        $this->assertSame('editor', $claims['role']);
        $this->assertSame(strtotime('2026-10-23 10:15:00'), $claims['exp']);
        $this->assertSame(3, count(explode('.', $token)));
    }

    public function test_expired_tokens_are_rejected(): void
    {
        $token = (new JwtService(self::SECRET, new FrozenClock('2026-10-23 10:00:00'), 900))->issue(['sub' => 1]);

        $this->expectException(AuthenticationException::class);

        (new JwtService(self::SECRET, new FrozenClock('2026-10-23 10:15:00'), 900))->decode($token);
    }

    public function test_tampered_payloads_are_rejected(): void
    {
        $jwt = new JwtService(self::SECRET, new FrozenClock('2026-10-23 10:00:00'), 900);
        [$header, , $signature] = explode('.', $jwt->issue(['sub' => 1, 'role' => 'viewer']));
        $forgedPayload = rtrim(strtr(base64_encode('{"sub":1,"role":"admin","exp":4102444800}'), '+/', '-_'), '=');

        $this->expectException(AuthenticationException::class);

        $jwt->decode("{$header}.{$forgedPayload}.{$signature}");
    }

    public function test_the_none_algorithm_is_rejected(): void
    {
        $jwt = new JwtService(self::SECRET, new FrozenClock('2026-10-23 10:00:00'), 900);
        $encode = fn (string $json): string => rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $token = $encode('{"alg":"none","typ":"JWT"}') . '.' . $encode('{"sub":1,"exp":4102444800}') . '.';

        $this->expectException(AuthenticationException::class);

        $jwt->decode($token);
    }

    public function test_tokens_signed_with_another_secret_are_rejected(): void
    {
        $token = (new JwtService('other-secret', new FrozenClock('2026-10-23 10:00:00'), 900))->issue(['sub' => 1]);

        $this->expectException(AuthenticationException::class);

        (new JwtService(self::SECRET, new FrozenClock('2026-10-23 10:00:00'), 900))->decode($token);
    }

    public function test_garbage_is_rejected_with_a_spanish_message(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('La sesión no es válida o expiró. Inicie sesión nuevamente.');

        (new JwtService(self::SECRET, new FrozenClock('2026-10-23 10:00:00'), 900))->decode('not.a.jwt');
    }
}
