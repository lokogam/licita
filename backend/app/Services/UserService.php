<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\ValidationException;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * User management rules (administrators only).
 */
final class UserService
{
    /**
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        $query = User::query();
        $query->orderBy('name');

        return $query->get();
    }

    /**
     * @param array<string, mixed> $data validated payload
     */
    public function create(array $data): User
    {
        return User::register(
            (string) $data['name'],
            (string) $data['email'],
            (string) $data['password'],
            Role::from((string) $data['role']),
        );
    }

    /**
     * Updates the given fields; the password changes only when a new one is sent.
     *
     * @param array<string, mixed> $data validated payload
     */
    public function update(User $user, array $data): User
    {
        if (isset($data['name']) && $data['name'] !== '') {
            $user->name = (string) $data['name'];
        }
        if (isset($data['email']) && $data['email'] !== '') {
            $user->email = User::normalizeEmail((string) $data['email']);
        }
        if (isset($data['role']) && $data['role'] !== '') {
            $user->role = Role::from((string) $data['role']);
        }
        if (isset($data['password']) && $data['password'] !== '') {
            $user->setPassword((string) $data['password']);
        }

        $user->save();

        return $user;
    }

    /**
     * Deletes a user; administrators cannot delete themselves (the app would be left without one).
     *
     * @throws ValidationException
     */
    public function delete(User $user, User $actor): void
    {
        if ($user->id === $actor->id) {
            throw new ValidationException(['user' => ['No puede eliminar su propio usuario.']]);
        }

        $user->delete();
    }
}
