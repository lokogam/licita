<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Database\Connection;

/**
 * Generates bd/01_schema.sql (a deliverable of the test) from a migrated database,
 * so the SQL script and the migrations never diverge.
 */
final class SchemaDumper
{
    private const HEADER = <<<'SQL'
        -- -----------------------------------------------------------------------------
        -- Tenders module - database structure (MySQL 8 / MariaDB 10.6+)
        -- Generated from backend/database/migrations with: php bin/console schema:dump
        --
        -- Table / column names are in English. Equivalence with the specification:
        --   tenders          -> ofertas
        --   activities       -> actividades
        --   tender_documents -> ofertas_documentos
        -- See README.md for the field-by-field mapping.
        -- -----------------------------------------------------------------------------

        SET NAMES utf8mb4;
        SET FOREIGN_KEY_CHECKS = 0;

        SQL;

    /**
     * @param list<string> $tables Tables in creation order (parents before children).
     */
    public function __construct(
        private readonly Connection $db,
        private readonly array $tables,
    ) {
    }

    public function dump(): string
    {
        $drops = array_map(
            fn (string $table): string => "DROP TABLE IF EXISTS `{$table}`;",
            array_reverse($this->tables),
        );

        $creates = array_map(fn (string $table): string => $this->createStatement($table), $this->tables);

        return self::HEADER
            . implode("\n", $drops) . "\n\n"
            . implode("\n\n", $creates) . "\n\n"
            . "SET FOREIGN_KEY_CHECKS = 1;\n";
    }

    private function createStatement(string $table): string
    {
        $row = (array) $this->db->selectOne("SHOW CREATE TABLE `{$table}`");
        $sql = (string) ($row['Create Table'] ?? '');

        // Counters are runtime state, not structure.
        $sql = (string) preg_replace('/ AUTO_INCREMENT=\d+/', '', $sql);

        return $sql . ';';
    }
}
