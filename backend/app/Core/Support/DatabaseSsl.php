<?php

declare(strict_types=1);

namespace App\Core\Support;

use PDO;
use RuntimeException;

/**
 * Builds PDO options for TLS connections to managed MySQL services (e.g. Aiven).
 *
 * The CA certificate is received as PEM text (environment variable) and written
 * to a file, because PDO only accepts a path.
 */
final class DatabaseSsl
{
    /**
     * @return array<int, string>
     */
    public static function options(?string $caPem, string $directory): array
    {
        $pem = trim(str_replace('\n', "\n", (string) $caPem));

        if ($pem === '') {
            return [];
        }

        $path = rtrim($directory, '/\\') . '/mysql-ca-' . md5($pem) . '.pem';

        if (!is_file($path) && file_put_contents($path, $pem . "\n") === false) {
            throw new RuntimeException('Unable to write the MySQL CA certificate.');
        }

        return [PDO::MYSQL_ATTR_SSL_CA => $path];
    }
}
