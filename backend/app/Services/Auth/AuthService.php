<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;

/**
 * Login, session refresh and logout.
 *
 * A session is a short-lived access token (JWT, kept in memory by the SPA)
 * plus a rotating refresh token (kept in an HttpOnly cookie).
 */
final class AuthService
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly RefreshTokenService $refreshTokens,
        private readonly LoginThrottle $throttle,
    ) {
    }

    /**
     * Checks the credentials and opens a session.
     *
     * The answer is the same whether the email exists or not, so accounts cannot be enumerated.
     *
     * @return array{user: User, access_token: string, refresh_token: string}
     * @throws TooManyAttemptsException
     * @throws AuthenticationException
     */
    public function login(string $email, string $password, string $ip): array
    {
        if ($this->throttle->tooManyAttempts($email, $ip)) {
            throw new TooManyAttemptsException($this->throttle->decayMinutes());
        }

        $user = User::findByEmail($email);
        if ($user === null || !$user->verifyPassword($password)) {
            $this->throttle->hit($email, $ip);
            throw new AuthenticationException('Correo o contraseña incorrectos.');
        }

        $this->throttle->clear($email);

        return $this->session($user, $this->refreshTokens->issue($user));
    }

    /**
     * Exchanges a refresh token for a new session (the old token stops working).
     *
     * @return array{user: User, access_token: string, refresh_token: string}
     * @throws AuthenticationException
     */
    public function refresh(string $refreshToken): array
    {
        [$user, $rotated] = $this->refreshTokens->rotate($refreshToken);

        return $this->session($user, $rotated);
    }

    public function logout(string $refreshToken): void
    {
        $this->refreshTokens->revoke($refreshToken);
    }

    public function accessTokenTtl(): int
    {
        return $this->jwt->ttl();
    }

    /**
     * @return array{user: User, access_token: string, refresh_token: string}
     */
    private function session(User $user, string $refreshToken): array
    {
        return [
            'user' => $user,
            'access_token' => $this->jwt->issue(['sub' => (string) $user->id, 'role' => $user->role->value]),
            'refresh_token' => $refreshToken,
        ];
    }
}
