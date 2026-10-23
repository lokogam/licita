<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * User roles and the abilities each one grants.
 */
enum Role: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';

    private const ABILITIES = [
        'viewer' => ['tenders.view'],
        'editor' => ['tenders.view', 'tenders.create', 'tenders.update', 'documents.manage'],
        'admin' => ['tenders.view', 'tenders.create', 'tenders.update', 'documents.manage', 'users.manage'],
    ];

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities(), true);
    }

    /**
     * @return list<string>
     */
    public function abilities(): array
    {
        return self::ABILITIES[$this->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Editor => 'Editor',
            self::Viewer => 'Lector',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }
}
