<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Core\Http\Request;
use App\Enums\Role;
use App\Models\User;
use App\Validation\Rules\Unique;

/**
 * Validation for creating a user (admin only).
 */
class StoreUserRequest extends FormRequest
{
    public const MIN_PASSWORD = 10;

    protected function rules(Request $request): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', $this->uniqueEmail(null)],
            'password' => ['required', 'string', 'min:' . self::MIN_PASSWORD, 'max:200'],
            'role' => ['required', 'in:' . implode(',', Role::values())],
        ];
    }

    protected function attributes(): array
    {
        return ['name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña', 'role' => 'rol'];
    }

    /**
     * Email not used by any other user (case-insensitive).
     */
    protected function uniqueEmail(?int $ignoreId): Unique
    {
        return new Unique(
            fn (mixed $email): bool => is_string($email) && User::query()
                ->where('email', User::normalizeEmail($email))
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->exists(),
            'Ya existe un usuario con ese correo.',
        );
    }
}
