<?php

/**
 * Users, rotating refresh tokens (stored hashed) and failed login attempts for throttling.
 */

declare(strict_types=1);

use App\Database\Migration;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function tables(): array
    {
        return ['users', 'refresh_tokens', 'login_attempts'];
    }

    public function up(Connection $db): void
    {
        $schema = $db->getSchemaBuilder();

        $schema->create('users', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name', 100);
            $table->string('email', 150);
            $table->string('password_hash', 255);
            $table->string('role', 20);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique('email', 'uq_users_email');
        });

        $schema->create('refresh_tokens', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->char('token_hash', 64);
            $table->dateTime('expires_at');
            $table->dateTime('revoked_at')->nullable();
            $table->dateTime('created_at');

            $table->unique('token_hash', 'uq_refresh_tokens_hash');
            $table->index('user_id', 'idx_refresh_tokens_user');
            $table->foreign('user_id', 'fk_refresh_tokens_user')->references('id')->on('users')->cascadeOnDelete();
        });

        $schema->create('login_attempts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('email', 150);
            $table->string('ip', 45);
            $table->dateTime('attempted_at');

            $table->index(['email', 'attempted_at'], 'idx_login_attempts_email');
            $table->index(['ip', 'attempted_at'], 'idx_login_attempts_ip');
        });
    }

    public function down(Connection $db): void
    {
        $schema = $db->getSchemaBuilder();
        $schema->dropIfExists('login_attempts');
        $schema->dropIfExists('refresh_tokens');
        $schema->dropIfExists('users');
    }
};
