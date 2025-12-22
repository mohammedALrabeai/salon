<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('access_token_hash', 64)->unique();
            $table->string('refresh_token_hash', 64)->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('refresh_expires_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('last_used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->string('device_name', 100)->nullable();
            $table->string('device_os', 50)->nullable();
            $table->string('device_version', 20)->nullable();
            $table->timestampsTz();

            $table->index('user_id');
            $table->index('expires_at');
            $table->index('refresh_expires_at');
            $table->index('revoked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
