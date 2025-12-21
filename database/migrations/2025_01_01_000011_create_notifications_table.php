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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['document_expiry', 'advance_request', 'day_closure', 'system', 'other']);
            $table->enum('target_type', ['user', 'role', 'branch', 'all']);
            $table->uuid('target_id')->nullable();
            $table->string('title', 200);
            $table->text('message');
            $table->jsonb('data')->default('{}');
            $table->text('action_url')->nullable();
            $table->enum('status', ['pending', 'sent', 'read', 'failed'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->jsonb('channels')->default('["in_app"]');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('expires_at')->nullable();

            $table->index(['target_type', 'target_id']);
            $table->index('type');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
