<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use App\Support\Clock;

/**
 * Issues and rotates refresh tokens.
 *
 * Each token can be used once: using it returns a new one and revokes the old one.
 * Presenting an already revoked token means it was stolen, so every token of that user is revoked.
 */
final class RefreshTokenService
{
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
            $this->revokeAllFor($record->user_id);
            throw new AuthenticationException();
        }

        if ($record->expires_at <= $this->clock->now()) {
            throw new AuthenticationException();
        }

        $record->revoked_at = $this->clock->now();
        $record->save();

        $user = $record->user;

        return [$user, $this->issue($user)];
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
