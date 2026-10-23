<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use RuntimeException;

/**
 * Runs versioned migrations (database/migrations/*.php) and records them in a `migrations` table.
 */
final class Migrator
{
    private const TABLE = 'migrations';

    public function __construct(
        private readonly Connection $db,
        private readonly string $path,
    ) {
    }

    /**
     * Runs every pending migration in a new batch and returns their names.
     *
     * @return list<string>
     */
    public function migrate(): array
    {
        $this->ensureRepository();

        $ran = $this->ranMigrations();
        $pending = array_values(array_diff(array_keys($this->files()), $ran));
        if ($pending === []) {
            return [];
        }

        $batch = $this->lastBatch() + 1;
        foreach ($pending as $name) {
            $this->load($name)->up($this->db);
            $this->db->table(self::TABLE)->insert(['migration' => $name, 'batch' => $batch]);
        }

        return $pending;
    }

    /**
     * Reverts the last batch (newest first) and returns the reverted names.
     *
     * @return list<string>
     */
    public function rollback(): array
    {
        $this->ensureRepository();

        $batch = $this->lastBatch();
        if ($batch === 0) {
            return [];
        }

        /** @var list<string> $names */
        $names = $this->db->table(self::TABLE)
            ->where('batch', $batch)
            ->orderByDesc('migration')
            ->pluck('migration')
            ->all();

        foreach ($names as $name) {
            $this->load($name)->down($this->db);
            $this->db->table(self::TABLE)->where('migration', $name)->delete();
        }

        return $names;
    }

    /**
     * Drops every table of the database and migrates from scratch.
     *
     * @return list<string>
     */
    public function fresh(): array
    {
        $this->db->statement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $this->db->getSchemaBuilder()->dropAllTables();
        } finally {
            $this->db->statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        return $this->migrate();
    }

    /**
     * Migration name => whether it has been run, in execution order.
     *
     * @return array<string, bool>
     */
    public function status(): array
    {
        $ran = $this->db->getSchemaBuilder()->hasTable(self::TABLE) ? $this->ranMigrations() : [];

        $status = [];
        foreach (array_keys($this->files()) as $name) {
            $status[$name] = in_array($name, $ran, true);
        }

        return $status;
    }

    /**
     * Creates the repository table. For a database already built from the bd/ scripts
     * (no repository yet), every migration whose tables already exist is marked as run.
     */
    private function ensureRepository(): void
    {
        $schema = $this->db->getSchemaBuilder();
        if ($schema->hasTable(self::TABLE)) {
            return;
        }

        $schema->create(self::TABLE, function (Blueprint $table): void {
            $table->increments('id');
            $table->string('migration', 191)->unique();
            $table->unsignedInteger('batch');
        });

        foreach (array_keys($this->files()) as $name) {
            $tables = $this->load($name)->tables();
            $existing = array_filter($tables, fn (string $table): bool => $schema->hasTable($table));

            if ($tables !== [] && count($existing) === count($tables)) {
                $this->db->table(self::TABLE)->insert(['migration' => $name, 'batch' => 1]);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function ranMigrations(): array
    {
        /** @var list<string> $names */
        $names = $this->db->table(self::TABLE)->orderBy('migration')->pluck('migration')->all();

        return $names;
    }

    private function lastBatch(): int
    {
        return (int) $this->db->table(self::TABLE)->max('batch');
    }

    /**
     * Migration name => file path, sorted by name (the timestamp prefix defines the order).
     *
     * @return array<string, string>
     */
    private function files(): array
    {
        $files = glob(rtrim($this->path, '/\\') . '/*.php') ?: [];
        sort($files);

        $migrations = [];
        foreach ($files as $file) {
            $migrations[basename($file, '.php')] = $file;
        }

        return $migrations;
    }

    private function load(string $name): Migration
    {
        $file = $this->files()[$name] ?? null;
        $migration = $file !== null ? require $file : null;

        if (!$migration instanceof Migration) {
            throw new RuntimeException("Migration [{$name}] must return an instance of " . Migration::class . '.');
        }

        return $migration;
    }
}
