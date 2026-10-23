<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Support;

use App\Core\Support\DatabaseSsl;
use PDO;
use PHPUnit\Framework\TestCase;

final class DatabaseSslTest extends TestCase
{
    private const PEM = "-----BEGIN CERTIFICATE-----\nMIIBfake\n-----END CERTIFICATE-----";

    public function test_it_returns_no_options_without_a_certificate(): void
    {
        $this->assertSame([], DatabaseSsl::options(null, sys_get_temp_dir()));
        $this->assertSame([], DatabaseSsl::options('   ', sys_get_temp_dir()));
    }

    public function test_it_writes_the_certificate_and_points_pdo_to_it(): void
    {
        $options = DatabaseSsl::options(self::PEM, sys_get_temp_dir());

        $path = $options[PDO::MYSQL_ATTR_SSL_CA];
        $this->assertFileExists($path);
        $this->assertSame(self::PEM . "\n", file_get_contents($path));
    }

    public function test_it_accepts_certificates_with_escaped_line_breaks(): void
    {
        $options = DatabaseSsl::options(str_replace("\n", '\n', self::PEM), sys_get_temp_dir());

        $this->assertSame(self::PEM . "\n", file_get_contents($options[PDO::MYSQL_ATTR_SSL_CA]));
    }
}
