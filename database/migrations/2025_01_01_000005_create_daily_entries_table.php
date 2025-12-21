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
        Schema::create('daily_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('sales', 10, 2)->default(0);
            $table->decimal('cash', 10, 2)->default(0);
            $table->decimal('expense', 10, 2)->default(0);
            $table->decimal('net', 10, 2)->storedAs('sales - cash - expense');
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->decimal('bonus', 10, 2)->default(0);
            $table->text('bonus_reason')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('transactions_count')->default(0);
            $table->enum('source', ['web', 'mobile', 'api'])->default('web');
            $table->boolean('is_locked')->default(false);
            $table->timestampTz('locked_at')->nullable();
            $table->foreignUuid('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestampTz('deleted_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['employee_id', 'date']);
            $table->index('date');
            $table->index('is_locked');
            $table->index('created_at');
            $table->index('deleted_at');
            $table->index(['branch_id', 'date']);
            $table->index(['employee_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_entries');
    }
};
