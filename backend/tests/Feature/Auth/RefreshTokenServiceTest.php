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
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::register('Ana Editor', 'ana@example.com', 'Secreta-2026!', Role::Editor);
    }

    public function test_tokens_are_stored_hashed_and_rotate_on_use(): void
    {
        $service = $this->service('2026-10-23 10:00:00');
        $token = $service->issue($this->owner);

        $this->assertSame(64, strlen($token));
        $this->assertNull(RefreshToken::query()->where('token_hash', $token)->first(), 'Plain token must not be stored.');

        [$user, $rotated] = $service->rotate($token);

        $this->assertSame($this->owner->id, $user->id);
        $this->assertNotSame($token, $rotated);
    }

    public function test_a_rotated_token_cannot_be_used_again_and_reuse_revokes_the_family(): void
    {
        $service = $this->service('2026-10-23 10:00:00');
        $original = $service->issue($this->owner);
        [, $current] = $service->rotate($original);

        $later = $this->service('2026-10-23 10:05:00');
        try {
            $later->rotate($original);
            $this->fail('Reusing a rotated token must fail.');
        } catch (AuthenticationException) {
        }

        $this->expectException(AuthenticationException::class);
        $later->rotate($current);
    }

    public function test_a_concurrent_refresh_within_the_grace_window_does_not_revoke_the_family(): void
    {
        // Two tabs share the cookie and refresh at the same moment: the second one loses
        // the race, but that is not a stolen token.
        $service = $this->service('2026-10-23 10:00:00');
        $original = $service->issue($this->owner);
        [, $current] = $service->rotate($original);

        try {
            $this->service('2026-10-23 10:00:10')->rotate($original);
            $this->fail('The losing request must still be rejected.');
        } catch (AuthenticationException) {
        }

        [$user] = $this->service('2026-10-23 10:00:20')->rotate($current);
        $this->assertSame($this->owner->id, $user->id);
    }

    public function test_issuing_prunes_expired_tokens_of_the_user(): void
    {
        $this->service('2026-09-01 10:00:00')->issue($this->owner);
        $this->service('2026-10-23 10:00:00')->issue($this->owner);

        $this->assertSame(1, RefreshToken::query()->where('user_id', $this->owner->id)->count());
    }

    public function test_expired_tokens_are_rejected(): void
    {
        $token = $this->service('2026-10-01 10:00:00')->issue($this->owner);

        $this->expectException(AuthenticationException::class);

        $this->service('2026-10-09 10:00:00')->rotate($token);
    }

    public function test_revoked_tokens_are_rejected(): void
    {
        $service = $this->service('2026-10-23 10:00:00');
        $token = $service->issue($this->owner);
        $service->revoke($token);

        $this->expectException(AuthenticationException::class);

        $service->rotate($token);
    }

    private function service(string $now): RefreshTokenService
    {
        return new RefreshTokenService(new FrozenClock($now), ttlDays: 7);
    }
}
