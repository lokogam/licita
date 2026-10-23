<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Core\Exceptions\HttpException;
use App\Support\CursorCodec;
use PHPUnit\Framework\TestCase;

final class CursorCodecTest extends TestCase
{
    public function test_it_round_trips_the_position(): void
    {
        $codec = new CursorCodec('secret-key');

        $this->assertSame(['id' => 42], $codec->decode($codec->encode(['id' => 42])));
    }

    public function test_tokens_are_url_safe(): void
    {
        $token = (new CursorCodec('secret-key'))->encode(['id' => 123456789]);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $token);
    }

    public function test_it_rejects_tampered_tokens(): void
    {
        $codec = new CursorCodec('secret-key');
        [, $signature] = explode('.', $codec->encode(['id' => 42]));
        $forged = rtrim(strtr(base64_encode('{"id":999}'), '+/', '-_'), '=') . '.' . $signature;

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('El cursor de paginación no es válido.');

        $codec->decode($forged);
    }

    public function test_it_rejects_tokens_signed_with_another_key(): void
    {
        $token = (new CursorCodec('other-key'))->encode(['id' => 42]);

        $this->expectException(HttpException::class);

        (new CursorCodec('secret-key'))->decode($token);
    }

    public function test_it_rejects_malformed_tokens(): void
    {
        $this->expectException(HttpException::class);

        (new CursorCodec('secret-key'))->decode('not-a-cursor');
    }
}
