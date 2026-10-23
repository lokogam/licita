<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Core\Http\Request;
use App\Enums\Role;

/**
 * Validation for editing a user: every field is optional, the password only changes when sent.
 */
final class UpdateUserRequest extends StoreUserRequest
{
    protected function rules(Request $request): array
    {
        return [
            'name' => ['string', 'max:100'],
            'email' => ['string', 'email', 'max:150', $this->uniqueEmail((int) $request->route('id'))],
            'password' => ['string', 'min:' . self::MIN_PASSWORD, 'max:200'],
            'role' => ['in:' . implode(',', Role::values())],
        ];
    }
}
