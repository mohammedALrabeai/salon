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
        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->enum('scope_type', ['system', 'branch', 'employee']);
            $table->uuid('scope_id')->nullable();
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->decimal('total_cash', 10, 2)->default(0);
            $table->decimal('total_expense', 10, 2)->default(0);
            $table->decimal('total_net', 10, 2)->default(0);
            $table->decimal('total_commission', 10, 2)->default(0);
            $table->decimal('total_bonus', 10, 2)->default(0);
            $table->unsignedInteger('entries_count')->default(0);
            $table->unsignedInteger('employees_count')->default(0);
            $table->unsignedInteger('transactions_count')->default(0);
            $table->decimal('avg_sale_value', 10, 2)->nullable();
            $table->decimal('avg_commission_rate', 5, 2)->nullable();
            $table->timestampTz('computed_at')->useCurrent();

            $table->unique(['date', 'scope_type', 'scope_id']);
            $table->index('date');
            $table->index(['scope_type', 'scope_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily');
    }
};
