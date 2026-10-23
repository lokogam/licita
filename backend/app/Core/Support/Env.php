<?php

declare(strict_types=1);

namespace App\Core\Support;

/**
 * Typed access to environment variables.
 */
final class Env
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if (is_array($value)) {
            return $default;
        }

        return $value === false ? $default : (string) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
