<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Tests\DatabaseTestCase;

final class UsersApiTest extends DatabaseTestCase
{
    public function test_admins_list_users(): void
    {
        User::register('Luis Lector', 'luis@example.com', 'Lector-Seguro-2026', Role::Viewer);

        $response = $this->call('GET', '/api/users');

        $this->assertSame(200, $response->status());
        $emails = array_column($this->json($response)['data'], 'email');
        $this->assertContains('luis@example.com', $emails);
        $this->assertArrayNotHasKey('password_hash', $this->json($response)['data'][0]);
    }

    public function test_admins_create_users_with_a_strong_password(): void
    {
        $response = $this->call('POST', '/api/users', [
            'name' => 'Nora Editor',
            'email' => 'Nora@Example.com',
            'password' => 'Nueva-Clave-2026',
            'role' => 'editor',
        ]);

        $this->assertSame(201, $response->status(), $response->content());
        $this->assertSame('nora@example.com', $this->json($response)['data']['email']);
        $this->assertTrue((User::findByEmail('nora@example.com')?->verifyPassword('Nueva-Clave-2026')) ?? false);
    }

    public function test_user_creation_is_validated(): void
    {
        User::register('Existente', 'existe@example.com', 'Clave-Segura-2026', Role::Viewer);

        $response = $this->call('POST', '/api/users', [
            'name' => '',
            'email' => 'existe@example.com',
            'password' => 'corta',
            'role' => 'superuser',
        ]);

        $this->assertSame(422, $response->status());
        $errors = $this->json($response)['errors'];
        $this->assertArrayHasKey('name', $errors);
        $this->assertSame(['Ya existe un usuario con ese correo.'], $errors['email']);
        $this->assertSame(['La contraseña debe tener al menos 10 caracteres.'], $errors['password']);
        $this->assertArrayHasKey('role', $errors);
    }

    public function test_admins_update_role_name_and_optionally_password(): void
    {
        $user = User::register('Luis Lector', 'luis@example.com', 'Lector-Seguro-2026', Role::Viewer);

        $response = $this->call('POST', "/api/users/{$user->id}", [
            '_method' => 'PATCH',
            'name' => 'Luis Editor',
            'role' => 'editor',
        ]);

        $this->assertSame(200, $response->status(), $response->content());
        $user->refresh();
        $this->assertSame('Luis Editor', $user->name);
        $this->assertSame(Role::Editor, $user->role);
        $this->assertTrue($user->verifyPassword('Lector-Seguro-2026'), 'Password unchanged when omitted.');
    }

    public function test_admins_delete_users_but_not_themselves(): void
    {
        $other = User::register('Temporal', 'temp@example.com', 'Temporal-Clave-2026', Role::Viewer);

        $this->assertSame(204, $this->call('DELETE', "/api/users/{$other->id}")->status());
        $this->assertNull(User::query()->find($other->id));

        $self = $this->call('DELETE', "/api/users/{$this->user?->id}");
        $this->assertSame(422, $self->status());
        $this->assertSame(['No puede eliminar su propio usuario.'], $this->json($self)['errors']['user']);
    }
}
