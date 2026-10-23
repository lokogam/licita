<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Core\Http\Request;

/**
 * Validation for the login form.
 */
final class LoginRequest extends FormRequest
{
    protected function rules(Request $request): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
        ];
    }

    protected function attributes(): array
    {
        return ['email' => 'correo', 'password' => 'contraseña'];
    }
}
