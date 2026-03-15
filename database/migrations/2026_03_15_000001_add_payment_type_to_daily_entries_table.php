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
        Schema::table('daily_entries', function (Blueprint $table) {
            $table->enum('payment_type', ['cash', 'network', 'purchases'])->default('cash')->after('expense');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_entries', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
