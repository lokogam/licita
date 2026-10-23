<?php

/**
 * Documents attached to a tender (ofertas_documentos).
 */

declare(strict_types=1);

use App\Database\Migration;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function tables(): array
    {
        return ['tender_documents'];
    }

    public function up(Connection $db): void
    {
        $db->getSchemaBuilder()->create('tender_documents', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('tender_id');
            $table->string('title', 100);
            $table->string('description', 200);
            $table->string('file_path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->dateTime('created_at');

            $table->index('tender_id', 'idx_tender_documents_tender');
            $table->foreign('tender_id', 'fk_tender_documents_tender')
                ->references('id')
                ->on('tenders')
                ->cascadeOnDelete();
        });
    }

    public function down(Connection $db): void
    {
        $db->getSchemaBuilder()->dropIfExists('tender_documents');
    }
};
