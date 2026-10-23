<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use App\Support\Clock;
use Illuminate\Support\Carbon;

/**
 * Issues and rotates refresh tokens.
 *
 * Each token can be used once: using it returns a new one and revokes the old one.
 * Presenting an already revoked token means it was stolen, so every token of that user is revoked.
 */
final class RefreshTokenService
{
    private const REUSE_GRACE_SECONDS = 30;

    public function __construct(
        private readonly Clock $clock,
        private readonly int $ttlDays = 7,
    ) {
    }

    public function ttlSeconds(): int
    {
        return $this->ttlDays * 86400;
    }

    /**
     * @return string plain token (only its hash is stored)
     */
    public function issue(User $user): string
    {
        $token = bin2hex(random_bytes(32));

        // Expired tokens are useless (even for reuse detection), so they are pruned here.
        RefreshToken::query()
            ->where('user_id', $user->id)
            ->where('expires_at', '<=', $this->clock->now()->format('Y-m-d H:i:s'))
            ->delete();

        RefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => self::hash($token),
            'expires_at' => $this->clock->now()->modify("+{$this->ttlDays} days"),
        ]);

        return $token;
    }

    /**
     * Exchanges a valid refresh token for a new one.
     *
     * @return array{0: User, 1: string}
     * @throws AuthenticationException
     */
    public function rotate(string $token): array
    {
        $record = $this->find($token);

        if ($record->revoked_at !== null) {
            $this->rejectReuse($record);
        }

        if ($record->expires_at <= $this->clock->now()) {
            throw new AuthenticationException();
        }

        // Conditional update: only one of two simultaneous requests can win the rotation.
        $won = RefreshToken::query()
            ->whereKey($record->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => Carbon::instance($this->clock->now())]);

        if ($won === 0) {
            throw new AuthenticationException();
        }

        $user = $record->user;

        return [$user, $this->issue($user)];
    }

    /**
     * A token revoked moments ago is most likely a second tab that lost the race,
     * so it is only rejected. An older one means the token was copied: every session ends.
     *
     * @throws AuthenticationException
     */
    private function rejectReuse(RefreshToken $record): never
    {
        $graceStart = $this->clock->now()->modify('-' . self::REUSE_GRACE_SECONDS . ' seconds');

        if ($record->revoked_at === null || $record->revoked_at < $graceStart) {
            $this->revokeAllFor($record->user_id);
        }

        throw new AuthenticationException();
    }

    public function revoke(string $token): void
    {
        RefreshToken::query()
            ->where('token_hash', self::hash($token))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $this->clock->now()]);
    }

    private function revokeAllFor(int $userId): void
    {
        RefreshToken::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $this->clock->now()]);
    }

    private function find(string $token): RefreshToken
    {
        $record = RefreshToken::query()->where('token_hash', self::hash($token))->first();

        if (!$record instanceof RefreshToken) {
            throw new AuthenticationException();
        }

        return $record;
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
