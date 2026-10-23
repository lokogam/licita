<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;

/**
 * JSON representation of a user (never includes the password hash).
 */
final class UserResource
{
    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     role: array{value: string, label: string},
     *     abilities: list<string>,
     *     created_at: string
     * }
     */
    public static function make(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => ['value' => $user->role->value, 'label' => $user->role->label()],
            'abilities' => $user->role->abilities(),
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
