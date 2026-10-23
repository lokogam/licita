<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Application user. Passwords are stored with Argon2id.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password_hash
 * @property Role $role
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class User extends Model
{
    protected $table = 'users';

    protected $fillable = ['name', 'email', 'role'];

    protected $hidden = ['password_hash'];

    /** @var array<string, string> */
    protected $casts = [
        'role' => Role::class,
    ];

    public static function register(string $name, string $email, string $password, Role $role): self
    {
        $user = new self(['name' => $name, 'email' => self::normalizeEmail($email), 'role' => $role]);
        $user->setPassword($password);
        $user->save();

        return $user;
    }

    public static function findByEmail(string $email): ?self
    {
        $user = self::query()->where('email', self::normalizeEmail($email))->first();

        return $user instanceof self ? $user : null;
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = password_hash($password, PASSWORD_ARGON2ID);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password_hash);
    }
}
