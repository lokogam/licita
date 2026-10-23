<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\Seeders\DemoUsersSeeder;
use App\Enums\Role;
use App\Models\User;
use Tests\DatabaseTestCase;

final class DemoUsersSeederTest extends DatabaseTestCase
{
    public function test_it_creates_one_demo_user_per_role(): void
    {
        (new DemoUsersSeeder())->run();

        foreach (DemoUsersSeeder::USERS as $demo) {
            $user = User::findByEmail($demo['email']);
            $this->assertNotNull($user);
            $this->assertSame($demo['role'], $user->role);
            $this->assertTrue($user->verifyPassword($demo['password']));
        }

        $this->assertEqualsCanonicalizing(
            [Role::Admin, Role::Editor, Role::Viewer],
            array_column(DemoUsersSeeder::USERS, 'role'),
        );
    }

    public function test_running_it_again_restores_tampered_demo_accounts(): void
    {
        $seeder = new DemoUsersSeeder();
        $seeder->run();

        $admin = User::findByEmail(DemoUsersSeeder::USERS[0]['email']);
        $this->assertNotNull($admin);
        $admin->role = Role::Viewer;
        $admin->setPassword('Otra-Clave-Distinta');
        $admin->save();

        $seeder->run();

        $admin->refresh();
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertTrue($admin->verifyPassword(DemoUsersSeeder::USERS[0]['password']));
        $this->assertSame(1, User::query()->where('email', $admin->email)->count());
    }

    public function test_sql_export_inserts_every_demo_user_idempotently(): void
    {
        $sql = (new DemoUsersSeeder())->toSql();

        foreach (DemoUsersSeeder::USERS as $demo) {
            $this->assertStringContainsString("'{$demo['email']}'", $sql);
            $this->assertStringNotContainsString($demo['password'], $sql);
        }
        $this->assertStringContainsString('INSERT IGNORE INTO `users`', $sql);
        $this->assertStringContainsString('$argon2id$', $sql);
    }
}
