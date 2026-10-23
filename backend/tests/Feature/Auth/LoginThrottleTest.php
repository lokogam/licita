<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Services\Auth\LoginThrottle;
use App\Support\FrozenClock;
use Tests\DatabaseTestCase;

final class LoginThrottleTest extends DatabaseTestCase
{
    public function test_it_locks_after_five_failures_within_fifteen_minutes(): void
    {
        $throttle = new LoginThrottle(new FrozenClock('2026-10-23 10:00:00'));

        for ($i = 0; $i < 4; $i++) {
            $throttle->hit('ana@example.com', '10.0.0.1');
        }
        $this->assertFalse($throttle->tooManyAttempts('ana@example.com', '10.0.0.1'));

        $throttle->hit('ana@example.com', '10.0.0.1');
        $this->assertTrue($throttle->tooManyAttempts('ana@example.com', '10.0.0.1'));
        $this->assertTrue($throttle->tooManyAttempts('ANA@example.com', '10.9.9.9'), 'Locked per email too.');
    }

    public function test_the_lock_expires(): void
    {
        $throttle = new LoginThrottle(new FrozenClock('2026-10-23 10:00:00'));
        for ($i = 0; $i < 5; $i++) {
            $throttle->hit('ana@example.com', '10.0.0.1');
        }

        $later = new LoginThrottle(new FrozenClock('2026-10-23 10:16:00'));

        $this->assertFalse($later->tooManyAttempts('ana@example.com', '10.0.0.1'));
    }

    public function test_a_successful_login_clears_the_attempts_for_that_email(): void
    {
        $throttle = new LoginThrottle(new FrozenClock('2026-10-23 10:00:00'));
        for ($i = 0; $i < 4; $i++) {
            $throttle->hit('ana@example.com', '10.0.0.1');
        }

        $throttle->clear('ana@example.com');
        $throttle->hit('ana@example.com', '10.0.0.1');

        $this->assertFalse($throttle->tooManyAttempts('ana@example.com', '10.0.0.2'));
    }
}
