<?php

declare(strict_types=1);

namespace App\Database\Seeders;

use App\Enums\Role;
use App\Models\User;

/**
 * One public demo account per role, so reviewers can try every permission level.
 *
 * Running it again restores the accounts (role and password), which undoes any
 * change made by visitors of the public demo.
 */
final class DemoUsersSeeder
{
    public const USERS = [
        [
            'name' => 'Administrador Demo',
            'email' => 'admin@suplos.test',
            'password' => 'Admin-Demo-2026',
            'role' => Role::Admin,
        ],
        [
            'name' => 'Editor Demo',
            'email' => 'editor@suplos.test',
            'password' => 'Editor-Demo-2026',
            'role' => Role::Editor,
        ],
        [
            'name' => 'Lector Demo',
            'email' => 'lector@suplos.test',
            'password' => 'Lector-Demo-2026',
            'role' => Role::Viewer,
        ],
    ];

    public function run(): void
    {
        foreach (self::USERS as $demo) {
            $user = User::findByEmail($demo['email']);

            if ($user === null) {
                User::register($demo['name'], $demo['email'], $demo['password'], $demo['role']);
                continue;
            }

            $user->name = $demo['name'];
            $user->role = $demo['role'];
            $user->setPassword($demo['password']);
            $user->save();
        }
    }

    /**
     * INSERT statements with Argon2id hashes, for initializing a database without PHP (bd/ scripts).
     */
    public function toSql(): string
    {
        $rows = array_map(
            fn (array $demo): string => sprintf(
                "  ('%s', '%s', '%s', '%s', NOW(), NOW())",
                $demo['name'],
                $demo['email'],
                password_hash($demo['password'], PASSWORD_ARGON2ID),
                $demo['role']->value,
            ),
            self::USERS,
        );

        return "-- Demo users (one per role). Passwords are listed in the README.\n"
            . "INSERT IGNORE INTO `users`\n"
            . "  (`name`, `email`, `password_hash`, `role`, `created_at`, `updated_at`) VALUES\n"
            . implode(",\n", $rows) . ";\n";
    }
}
