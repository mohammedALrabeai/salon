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
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('phone', 20)->unique();
            $table->string('email', 100)->nullable();
            $table->string('national_id', 20)->nullable();
            $table->string('passport_number', 20)->nullable();
            $table->enum('role', ['barber', 'manager', 'receptionist', 'other'])->default('barber');
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'freelance'])->default('full_time');
            $table->decimal('commission_rate', 5, 2)->default(50.00);
            $table->enum('commission_type', ['percentage', 'fixed', 'tiered'])->default('percentage');
            $table->decimal('base_salary', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'on_leave', 'suspended'])->default('active');
            $table->text('avatar_url')->nullable();
            $table->text('bio')->nullable();
            $table->jsonb('skills')->default('[]');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestampTz('deleted_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('role');
            $table->index('status');
            $table->index('hire_date');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
