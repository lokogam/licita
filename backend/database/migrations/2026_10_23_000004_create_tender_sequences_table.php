<?php

/**
 * Last consecutive number issued per year (row-locked when generating O-0001-YY).
 */

declare(strict_types=1);

use App\Database\Migration;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function tables(): array
    {
        return ['tender_sequences'];
    }

    public function up(Connection $db): void
    {
        $db->getSchemaBuilder()->create('tender_sequences', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number');
        });
    }

    public function down(Connection $db): void
    {
        $db->getSchemaBuilder()->dropIfExists('tender_sequences');
    }
};
