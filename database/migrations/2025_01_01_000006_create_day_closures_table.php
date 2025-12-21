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
        Schema::create('day_closures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->decimal('total_cash', 10, 2)->default(0);
            $table->decimal('total_expense', 10, 2)->default(0);
            $table->decimal('total_net', 10, 2)->default(0);
            $table->decimal('total_commission', 10, 2)->default(0);
            $table->decimal('total_bonus', 10, 2)->default(0);
            $table->unsignedInteger('entries_count')->default(0);
            $table->unsignedInteger('employees_count')->default(0);
            $table->foreignUuid('closed_by')->constrained('users');
            $table->timestampTz('closed_at')->useCurrent();
            $table->text('pdf_url')->nullable();
            $table->timestampTz('pdf_generated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['branch_id', 'date']);
            $table->index('date');
            $table->index('closed_by');
            $table->index('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('day_closures');
    }
};
