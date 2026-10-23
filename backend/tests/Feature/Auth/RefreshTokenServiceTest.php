<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\RefreshToken;
use App\Models\User;
use App\Services\Auth\AuthenticationException;
use App\Services\Auth\RefreshTokenService;
use App\Support\FrozenClock;
use Tests\DatabaseTestCase;

final class RefreshTokenServiceTest extends DatabaseTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::register('Ana Editor', 'ana@example.com', 'Secreta-2026!', Role::Editor);
    }

    public function test_tokens_are_stored_hashed_and_rotate_on_use(): void
    {
        $service = $this->service('2026-10-23 10:00:00');
        $token = $service->issue($this->user);

        $this->assertSame(64, strlen($token));
        $this->assertNull(RefreshToken::query()->where('token_hash', $token)->first(), 'Plain token must not be stored.');

        [$user, $rotated] = $service->rotate($token);

        $this->assertSame($this->user->id, $user->id);
        $this->assertNotSame($token, $rotated);
    }

    public function test_a_rotated_token_cannot_be_used_again_and_reuse_revokes_the_family(): void
    {
        $service = $this->service('2026-10-23 10:00:00');
        $original = $service->issue($this->user);
        [, $current] = $service->rotate($original);

        try {
            $service->rotate($original);
            $this->fail('Reusing a rotated token must fail.');
        } catch (AuthenticationException) {
        }

        $this->expectException(AuthenticationException::class);
        $service->rotate($current);
    }

    public function test_expired_tokens_are_rejected(): void
    {
        $token = $this->service('2026-10-01 10:00:00')->issue($this->user);

        $this->expectException(AuthenticationException::class);

        $this->service('2026-10-09 10:00:00')->rotate($token);
    }

    public function test_revoked_tokens_are_rejected(): void
    {
        $service = $this->service('2026-10-23 10:00:00');
        $token = $service->issue($this->user);
        $service->revoke($token);

        $this->expectException(AuthenticationException::class);

        $service->rotate($token);
    }

    private function service(string $now): RefreshTokenService
    {
        return new RefreshTokenService(new FrozenClock($now), ttlDays: 7);
    }
}
