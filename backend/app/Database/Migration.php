<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Database\Connection;

/**
 * A reversible schema change. Files in database/migrations return an instance of this class.
 */
abstract class Migration
{
    abstract public function up(Connection $db): void;

    abstract public function down(Connection $db): void;
}
