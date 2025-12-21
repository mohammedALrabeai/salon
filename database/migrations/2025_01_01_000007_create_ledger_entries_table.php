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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('party_type', ['employee', 'branch', 'supplier', 'customer']);
            $table->uuid('party_id');
            $table->date('date');
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('amount', 10, 2);
            $table->text('description');
            $table->string('category', 50)->nullable();
            $table->enum('source', ['manual', 'advance_request', 'salary', 'closure', 'other']);
            $table->uuid('reference_id')->nullable();
            $table->string('reference_type', 30)->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'other'])->nullable();
            $table->text('attachment_url')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('confirmed');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestampTz('deleted_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['party_type', 'party_id']);
            $table->index('date');
            $table->index('type');
            $table->index('source');
            $table->index(['reference_type', 'reference_id']);
            $table->index('status');
            $table->index('created_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
