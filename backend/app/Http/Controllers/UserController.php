<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use OpenApi\Attributes as OA;

/**
 * User management (administrators only).
 */
final class UserController
{
    public function __construct(private readonly UserService $users)
    {
    }

    /**
     * GET /api/users
     */
    #[OA\Get(
        path: '/api/users',
        summary: 'Listar usuarios',
        tags: ['Usuarios'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuarios ordenados por nombre',
                content: new OA\JsonContent(properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/User'),
                    ),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
        ],
    )]
    public function index(): JsonResponse
    {
        $users = $this->users->all()->map(fn (User $user): array => UserResource::make($user));

        return new JsonResponse(['data' => $users->all()]);
    }

    /**
     * POST /api/users
     */
    #[OA\Post(
        path: '/api/users',
        summary: 'Crear usuario',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserInput'),
        ),
        tags: ['Usuarios'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario creado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/UnprocessableEntity', response: 422),
        ],
    )]
    public function store(Request $request, StoreUserRequest $validation): JsonResponse
    {
        $user = $this->users->create($validation->validate($request));

        return new JsonResponse(['data' => UserResource::make($user)], 201);
    }

    /**
     * PATCH /api/users/{id} - partial update; the password changes only when sent.
     */
    #[OA\Patch(
        path: '/api/users/{id}',
        summary: 'Editar usuario (la contraseña solo cambia si se envía)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserInput'),
        ),
        tags: ['Usuarios'],
        parameters: [new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario actualizado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/UnprocessableEntity', response: 422),
        ],
    )]
    public function update(Request $request, UpdateUserRequest $validation): JsonResponse
    {
        $user = $this->users->update($this->find($request), $validation->validate($request));

        return new JsonResponse(['data' => UserResource::make($user)]);
    }

    /**
     * DELETE /api/users/{id}
     */
    #[OA\Delete(
        path: '/api/users/{id}',
        summary: 'Eliminar usuario (no puede eliminarse a sí mismo)',
        tags: ['Usuarios'],
        parameters: [new OA\PathParameter(name: 'id', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Usuario eliminado'),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/UnprocessableEntity', response: 422),
        ],
    )]
    public function destroy(Request $request): Response
    {
        /** @var User $actor */
        $actor = $request->attribute('user');
        $this->users->delete($this->find($request), $actor);

        return new Response('', 204);
    }

    private function find(Request $request): User
    {
        $user = User::query()->find((int) $request->route('id'));

        if (!$user instanceof User) {
            throw new NotFoundHttpException('El usuario solicitado no existe.');
        }

        return $user;
    }
}
