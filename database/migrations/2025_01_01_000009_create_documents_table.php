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
        $driver = Schema::getConnection()->getDriverName();

        $statusExpression = match ($driver) {
            'pgsql' => "CASE WHEN expiry_date IS NULL THEN 'safe' WHEN expiry_date < CURRENT_DATE THEN 'expired' WHEN expiry_date <= (CURRENT_DATE + INTERVAL '15 days') THEN 'urgent' WHEN expiry_date <= (CURRENT_DATE + INTERVAL '60 days') THEN 'near' ELSE 'safe' END",
            'mysql' => "CASE WHEN expiry_date IS NULL THEN 'safe' WHEN expiry_date < CURRENT_DATE THEN 'expired' WHEN expiry_date <= (CURRENT_DATE + INTERVAL 15 DAY) THEN 'urgent' WHEN expiry_date <= (CURRENT_DATE + INTERVAL 60 DAY) THEN 'near' ELSE 'safe' END",
            default => "CASE WHEN expiry_date IS NULL THEN 'safe' WHEN date(expiry_date) < date('now') THEN 'expired' WHEN date(expiry_date) <= date('now', '+15 days') THEN 'urgent' WHEN date(expiry_date) <= date('now', '+60 days') THEN 'near' ELSE 'safe' END",
        };

        $daysRemainingExpression = match ($driver) {
            'pgsql' => "CASE WHEN expiry_date IS NULL THEN NULL ELSE (expiry_date - CURRENT_DATE) END",
            'mysql' => 'DATEDIFF(expiry_date, CURRENT_DATE)',
            default => "CASE WHEN expiry_date IS NULL THEN NULL ELSE CAST((julianday(expiry_date) - julianday('now')) AS INTEGER) END",
        };

        Schema::create('documents', function (Blueprint $table) use ($statusExpression, $daysRemainingExpression) {
            $table->uuid('id')->primary();
            $table->enum('owner_type', ['employee', 'branch', 'company']);
            $table->uuid('owner_id');
            $table->string('type', 50);
            $table->string('number', 50)->nullable();
            $table->string('title', 200)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 20)->storedAs($statusExpression);
            $table->integer('days_remaining')->storedAs($daysRemainingExpression);
            $table->unsignedInteger('notify_before_days')->default(30);
            $table->timestampTz('last_notified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestampTz('deleted_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['owner_type', 'owner_id']);
            $table->index('type');
            $table->index('status');
            $table->index('expiry_date');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
