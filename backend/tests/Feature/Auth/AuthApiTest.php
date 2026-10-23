<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Tests\DatabaseTestCase;

final class AuthApiTest extends DatabaseTestCase
{
    private const PASSWORD = 'Editor-Seguro-2026';

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsGuest();
        User::register('Ana Editor', 'ana@example.com', self::PASSWORD, Role::Editor);
    }

    public function test_login_returns_an_access_token_and_sets_the_refresh_cookie(): void
    {
        $response = $this->login('ANA@example.com', self::PASSWORD);

        $this->assertSame(200, $response->status(), $response->content());
        $data = $this->json($response)['data'];
        $this->assertSame('Bearer', $data['token_type']);
        $this->assertSame(900, $data['expires_in']);
        $this->assertNotEmpty($data['access_token']);
        $this->assertSame('ana@example.com', $data['user']['email']);
        $this->assertSame(['value' => 'editor', 'label' => 'Editor'], $data['user']['role']);
        $this->assertContains('tenders.create', $data['user']['abilities']);

        $cookie = $response->headers()['Set-Cookie'];
        $this->assertStringContainsString('HttpOnly', $cookie);
        $this->assertStringContainsString('SameSite=Strict', $cookie);
        $this->assertStringContainsString('Path=/api/auth', $cookie);
        $this->assertSame(64, strlen((string) $this->cookieFrom($response, 'refresh_token')));
    }

    public function test_wrong_credentials_get_the_same_generic_answer(): void
    {
        foreach ([['ana@example.com', 'incorrecta'], ['nadie@example.com', self::PASSWORD]] as [$email, $password]) {
            $response = $this->login($email, $password);

            $this->assertSame(401, $response->status());
            $this->assertSame('Correo o contraseña incorrectos.', $this->json($response)['message']);
        }
    }

    public function test_login_validates_the_payload(): void
    {
        $response = $this->call('POST', '/api/auth/login', ['email' => '', 'password' => '']);

        $this->assertSame(422, $response->status());
        $this->assertArrayHasKey('email', $this->json($response)['errors']);
        $this->assertArrayHasKey('password', $this->json($response)['errors']);
    }

    public function test_it_locks_the_account_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login('ana@example.com', 'incorrecta');
        }

        $response = $this->login('ana@example.com', self::PASSWORD);

        $this->assertSame(429, $response->status());
        $this->assertSame(
            'Demasiados intentos fallidos. Intente de nuevo en 15 minutos.',
            $this->json($response)['message'],
        );
        $this->assertSame('900', $response->headers()['Retry-After']);
    }

    public function test_refresh_rotates_the_cookie_and_rejects_reuse(): void
    {
        $first = $this->cookieFrom($this->login('ana@example.com', self::PASSWORD), 'refresh_token');

        $response = $this->call('POST', '/api/auth/refresh', cookies: ['refresh_token' => (string) $first]);

        $this->assertSame(200, $response->status(), $response->content());
        $this->assertNotEmpty($this->json($response)['data']['access_token']);
        $this->assertNotSame($first, $this->cookieFrom($response, 'refresh_token'));

        $reuse = $this->call('POST', '/api/auth/refresh', cookies: ['refresh_token' => (string) $first]);
        $this->assertSame(401, $reuse->status());
    }

    public function test_refresh_without_cookie_is_rejected(): void
    {
        $this->assertSame(401, $this->call('POST', '/api/auth/refresh')->status());
    }

    public function test_logout_revokes_the_refresh_token_and_clears_the_cookie(): void
    {
        $token = (string) $this->cookieFrom($this->login('ana@example.com', self::PASSWORD), 'refresh_token');

        $response = $this->call('POST', '/api/auth/logout', cookies: ['refresh_token' => $token]);

        $this->assertSame(204, $response->status());
        $this->assertStringContainsString('Max-Age=0', $response->headers()['Set-Cookie']);
        $this->assertSame(401, $this->call('POST', '/api/auth/refresh', cookies: ['refresh_token' => $token])->status());
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $accessToken = $this->json($this->login('ana@example.com', self::PASSWORD))['data']['access_token'];

        $response = $this->call('GET', '/api/auth/me', headers: ['Authorization' => "Bearer {$accessToken}"]);

        $this->assertSame(200, $response->status());
        $this->assertSame('Ana Editor', $this->json($response)['data']['name']);
        $this->assertSame(401, $this->call('GET', '/api/auth/me')->status());
    }

    private function login(string $email, string $password): \App\Core\Http\Response
    {
        return $this->call('POST', '/api/auth/login', ['email' => $email, 'password' => $password]);
    }
}
