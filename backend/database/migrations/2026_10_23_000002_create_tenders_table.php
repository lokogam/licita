<?php

/**
 * Tenders (ofertas). starts_at / closes_at are stored generated columns so status
 * filters (scheduled / open / closed) can use indexes.
 */

declare(strict_types=1);

use App\Database\Migration;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(Connection $db): void
    {
        $db->getSchemaBuilder()->create('tenders', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('consecutive', 20);
            $table->string('subject', 150);
            $table->string('description', 400);
            $table->string('currency', 3);
            $table->decimal('budget', 15, 2);
            $table->unsignedInteger('activity_id');
            $table->date('start_date');
            $table->time('start_time');
            $table->date('closing_date');
            $table->time('closing_time');
            $table->string('status', 20);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->dateTime('starts_at')->nullable()->storedAs('timestamp(`start_date`,`start_time`)');
            $table->dateTime('closes_at')->nullable()->storedAs('timestamp(`closing_date`,`closing_time`)');

            $table->unique('consecutive', 'uq_tenders_consecutive');
            $table->index('subject', 'idx_tenders_subject');
            $table->index('created_at', 'idx_tenders_created_at');
            $table->index('starts_at', 'idx_tenders_starts_at');
            $table->index('closes_at', 'idx_tenders_closes_at');
            $table->foreign('activity_id', 'fk_tenders_activity')->references('id')->on('activities');
        });

        $db->statement(
            "ALTER TABLE tenders ADD CONSTRAINT chk_tenders_currency CHECK (currency IN ('COP', 'USD', 'EUR'))",
        );
        $db->statement('ALTER TABLE tenders ADD CONSTRAINT chk_tenders_budget CHECK (budget > 0)');
    }

    public function down(Connection $db): void
    {
        $db->getSchemaBuilder()->dropIfExists('tenders');
    }
};
