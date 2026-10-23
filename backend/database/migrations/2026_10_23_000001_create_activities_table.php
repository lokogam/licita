<?php

/**
 * UNSPSC goods and services classifier (actividades).
 */

declare(strict_types=1);

use App\Database\Migration;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function tables(): array
    {
        return ['activities'];
    }

    public function up(Connection $db): void
    {
        $db->getSchemaBuilder()->create('activities', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('segment_code');
            $table->string('segment', 200);
            $table->unsignedInteger('family_code');
            $table->string('family', 200);
            $table->unsignedInteger('class_code');
            $table->string('class_name', 200);
            $table->unsignedInteger('product_code');
            $table->string('product', 200);

            $table->unique('product_code', 'uq_activities_product_code');
            $table->index('product', 'idx_activities_product');
        });
    }

    public function down(Connection $db): void
    {
        $db->getSchemaBuilder()->dropIfExists('activities');
    }
};
