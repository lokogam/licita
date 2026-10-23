<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Role;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    /**
     * @return iterable<string, array{Role, string, bool}>
     */
    public static function permissions(): iterable
    {
        yield 'viewer can view' => [Role::Viewer, 'tenders.view', true];
        yield 'viewer cannot create' => [Role::Viewer, 'tenders.create', false];
        yield 'viewer cannot manage documents' => [Role::Viewer, 'documents.manage', false];
        yield 'editor can create' => [Role::Editor, 'tenders.create', true];
        yield 'editor can update' => [Role::Editor, 'tenders.update', true];
        yield 'editor can manage documents' => [Role::Editor, 'documents.manage', true];
        yield 'editor cannot manage users' => [Role::Editor, 'users.manage', false];
        yield 'admin can manage users' => [Role::Admin, 'users.manage', true];
        yield 'admin can do everything else' => [Role::Admin, 'documents.manage', true];
        yield 'unknown abilities are denied' => [Role::Admin, 'tenders.destroy-everything', false];
    }

    #[DataProvider('permissions')]
    public function test_abilities_per_role(Role $role, string $ability, bool $allowed): void
    {
        $this->assertSame($allowed, $role->can($ability));
    }

    public function test_it_exposes_spanish_labels(): void
    {
        $this->assertSame('Administrador', Role::Admin->label());
        $this->assertSame('Editor', Role::Editor->label());
        $this->assertSame('Lector', Role::Viewer->label());
    }
}
