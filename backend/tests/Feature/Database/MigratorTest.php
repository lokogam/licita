<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\Migrator;
use App\Database\SchemaDumper;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Runs against a scratch database so creating and dropping tables never affects other tests.
 */
final class MigratorTest extends TestCase
{
    private const DATABASE = 'licita_migrations_test';

    private const TABLES = [
        'activities',
        'tenders',
        'tender_documents',
        'tender_sequences',
        'users',
        'refresh_tokens',
        'login_attempts',
    ];

    private Connection $db;

    private Migrator $migrator;

    protected function setUp(): void
    {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s', getenv('DB_HOST'), getenv('DB_PORT')),
            (string) getenv('DB_USERNAME'),
            (string) getenv('DB_PASSWORD'),
        );
        $pdo->exec('DROP DATABASE IF EXISTS `' . self::DATABASE . '`');
        $pdo->exec('CREATE DATABASE `' . self::DATABASE . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $capsule = new Capsule();
        $capsule->addConnection(array_merge(require __DIR__ . '/../../../config/database.php', [
            'database' => self::DATABASE,
        ]), 'migrations_test');
        $this->db = $capsule->getConnection('migrations_test');

        $this->migrator = new Migrator($this->db, __DIR__ . '/../../../database/migrations');
    }

    public function test_it_runs_pending_migrations_in_a_batch(): void
    {
        $ran = $this->migrator->migrate();

        $this->assertCount(5, $ran);
        foreach (self::TABLES as $table) {
            $this->assertTrue($this->db->getSchemaBuilder()->hasTable($table), "Missing table {$table}");
        }
        $this->assertSame([1], $this->db->table('migrations')->distinct()->pluck('batch')->all());
        $this->assertSame([], $this->migrator->migrate(), 'Second run must be a no-op.');
    }

    public function test_it_reports_the_status_of_each_migration(): void
    {
        $this->assertSame([false, false, false, false, false], array_values($this->migrator->status()));

        $this->migrator->migrate();

        $status = $this->migrator->status();
        $this->assertSame([true, true, true, true, true], array_values($status));
        $this->assertStringContainsString('create_tenders_table', array_keys($status)[1]);
    }

    public function test_it_rolls_back_the_last_batch(): void
    {
        $this->migrator->migrate();

        $rolledBack = $this->migrator->rollback();

        $this->assertCount(5, $rolledBack);
        foreach (self::TABLES as $table) {
            $this->assertFalse($this->db->getSchemaBuilder()->hasTable($table));
        }
        $this->assertSame(0, $this->db->table('migrations')->count());
    }

    public function test_fresh_drops_everything_and_migrates_again(): void
    {
        $this->migrator->migrate();
        $this->db->statement('CREATE TABLE leftover (id INT PRIMARY KEY)');

        $this->migrator->fresh();

        $this->assertFalse($this->db->getSchemaBuilder()->hasTable('leftover'));
        $this->assertTrue($this->db->getSchemaBuilder()->hasTable('tenders'));
    }

    public function test_it_baselines_databases_created_from_the_bd_scripts(): void
    {
        $this->db->unprepared((string) file_get_contents(__DIR__ . '/../../../../bd/01_schema.sql'));

        $ran = $this->migrator->migrate();

        $this->assertSame([], $ran, 'Legacy schema must be marked as migrated, not re-created.');
        $this->assertSame([true, true, true, true, true], array_values($this->migrator->status()));
    }

    public function test_baseline_only_marks_migrations_whose_tables_exist(): void
    {
        $this->migrator->migrate();
        // Simulate a v1.0 database: created from the bd scripts before the auth tables existed.
        $this->db->getSchemaBuilder()->drop('migrations');
        foreach (['login_attempts', 'refresh_tokens', 'users'] as $table) {
            $this->db->getSchemaBuilder()->drop($table);
        }

        $ran = $this->migrator->migrate();

        $this->assertSame(['2026_10_24_000005_create_auth_tables'], $ran);
        $this->assertTrue($this->db->getSchemaBuilder()->hasTable('users'));
    }

    public function test_the_bd_schema_script_matches_the_migrations(): void
    {
        $this->migrator->migrate();

        $dump = (new SchemaDumper($this->db, self::TABLES))->dump();

        $this->assertSame(
            (string) file_get_contents(__DIR__ . '/../../../../bd/01_schema.sql'),
            $dump,
            'Run "php bin/console schema:dump" after changing a migration.',
        );
    }
}
