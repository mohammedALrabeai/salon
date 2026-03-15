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
        Schema::table('day_closures', function (Blueprint $table) {
            $table->decimal('total_cash_payments', 10, 2)->default(0)->after('total_bonus');
            $table->decimal('total_network_payments', 10, 2)->default(0)->after('total_cash_payments');
            $table->decimal('total_purchases_payments', 10, 2)->default(0)->after('total_network_payments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('day_closures', function (Blueprint $table) {
            $table->dropColumn(['total_cash_payments', 'total_network_payments', 'total_purchases_payments']);
        });
    }
};
