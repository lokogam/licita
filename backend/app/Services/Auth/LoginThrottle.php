<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Support\Clock;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Locks logins after too many failures for the same email or the same IP within a time window.
 */
final class LoginThrottle
{
    public function __construct(
        private readonly Clock $clock,
        private readonly int $maxAttempts = 5,
        private readonly int $decayMinutes = 15,
    ) {
    }

    public function decayMinutes(): int
    {
        return $this->decayMinutes;
    }

    public function hit(string $email, string $ip): void
    {
        Capsule::table('login_attempts')->insert([
            'email' => User::normalizeEmail($email),
            'ip' => $ip,
            'attempted_at' => $this->clock->now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function tooManyAttempts(string $email, string $ip): bool
    {
        $since = $this->clock->now()->modify("-{$this->decayMinutes} minutes")->format('Y-m-d H:i:s');
        $table = fn () => Capsule::table('login_attempts')->where('attempted_at', '>', $since);

        return $table()->where('email', User::normalizeEmail($email))->count() >= $this->maxAttempts
            || $table()->where('ip', $ip)->count() >= $this->maxAttempts;
    }

    public function clear(string $email): void
    {
        Capsule::table('login_attempts')->where('email', User::normalizeEmail($email))->delete();
    }
}
