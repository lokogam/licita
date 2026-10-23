<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Http\RefreshCookie;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthenticationException;
use App\Services\Auth\AuthService;
use OpenApi\Attributes as OA;

/**
 * Session endpoints: login, refresh, logout and current user.
 */
final class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly RefreshCookie $cookie,
    ) {
    }

    /**
     * POST /api/auth/login
     */
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Iniciar sesión (el refresh token se entrega en una cookie HttpOnly)',
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@licita.test'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ],
            ),
        ),
        tags: ['Autenticación'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión iniciada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Session'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/UnprocessableEntity', response: 422),
            new OA\Response(ref: '#/components/responses/TooManyRequests', response: 429),
        ],
    )]
    public function login(Request $request, LoginRequest $validation): Response
    {
        $data = $validation->validate($request);
        $session = $this->auth->login((string) $data['email'], (string) $data['password'], $request->ip());

        return $this->sessionResponse($session);
    }

    /**
     * POST /api/auth/refresh - rotates the refresh cookie and returns a new access token.
     */
    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Renovar el access token con la cookie refresh_token (la cookie se rota)',
        security: [],
        tags: ['Autenticación'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión renovada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/Session'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
        ],
    )]
    public function refresh(Request $request): Response
    {
        $token = $this->cookie->read($request) ?? throw new AuthenticationException();

        return $this->sessionResponse($this->auth->refresh($token));
    }

    /**
     * POST /api/auth/logout - revokes the refresh token and clears the cookie.
     */
    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Cerrar sesión',
        security: [],
        tags: ['Autenticación'],
        responses: [new OA\Response(response: 204, description: 'Sesión cerrada')],
    )]
    public function logout(Request $request): Response
    {
        $token = $this->cookie->read($request);
        if ($token !== null) {
            $this->auth->logout($token);
        }

        return $this->cookie->forget(new Response('', 204));
    }

    /**
     * GET /api/auth/me
     */
    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Usuario autenticado, con su rol y permisos',
        tags: ['Autenticación'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario actual',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
        ],
    )]
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attribute('user');

        return new JsonResponse(['data' => UserResource::make($user)]);
    }

    /**
     * @param array{user: User, access_token: string, refresh_token: string} $session
     */
    private function sessionResponse(array $session): Response
    {
        $response = new JsonResponse(['data' => [
            'access_token' => $session['access_token'],
            'token_type' => 'Bearer',
            'expires_in' => $this->auth->accessTokenTtl(),
            'user' => UserResource::make($session['user']),
        ]]);

        $response = $response->withHeaders(['Cache-Control' => 'no-store']);

        return $this->cookie->attach($response, $session['refresh_token']);
    }
}
