<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1. Rename columns
        if (Schema::hasTable('daily_entries')) {
            Schema::table('daily_entries', function (Blueprint $table) use ($driver) {
                if ($driver !== 'sqlite') {
                    $table->dropForeign(['employee_id']);
                }
                $table->renameColumn('employee_id', 'user_id');
            });

            if ($driver !== 'sqlite') {
                Schema::table('daily_entries', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                });
            }
        }

        if (Schema::hasTable('advance_requests')) {
            Schema::table('advance_requests', function (Blueprint $table) use ($driver) {
                if ($driver !== 'sqlite') {
                    $table->dropForeign(['employee_id']);
                }
                $table->renameColumn('employee_id', 'user_id');
            });

            if ($driver !== 'sqlite') {
                Schema::table('advance_requests', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                });
            }
        }

        // 2. Update ENUMs based on the driver

        // documents.owner_type = ['user', 'branch', 'company']
        if (Schema::hasTable('documents')) {
            DB::table('documents')
                ->where('owner_type', 'employee')
                ->update(['owner_type' => 'user']);

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_owner_type_check");
                DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_owner_type_check CHECK (owner_type in ('user','branch','company'))");
            } elseif ($driver === 'mysql') {
                DB::statement("ALTER TABLE documents MODIFY COLUMN owner_type ENUM('user', 'branch', 'company') NOT NULL");
            }
        }

        // ledger_entries.party_type = ['user', 'branch', 'supplier', 'customer']
        if (Schema::hasTable('ledger_entries')) {
            DB::table('ledger_entries')
                ->where('party_type', 'employee')
                ->update(['party_type' => 'user']);

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE ledger_entries DROP CONSTRAINT IF EXISTS ledger_entries_party_type_check");
                DB::statement("ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entries_party_type_check CHECK (party_type in ('user','branch','supplier','customer'))");
            } elseif ($driver === 'mysql') {
                DB::statement("ALTER TABLE ledger_entries MODIFY COLUMN party_type ENUM('user', 'branch', 'supplier', 'customer') NOT NULL");
            }
        }

        // analytics_daily.scope_type = ['system', 'branch', 'user']
        if (Schema::hasTable('analytics_daily')) {
            DB::table('analytics_daily')
                ->where('scope_type', 'employee')
                ->update(['scope_type' => 'user']);

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE analytics_daily DROP CONSTRAINT IF EXISTS analytics_daily_scope_type_check");
                DB::statement("ALTER TABLE analytics_daily ADD CONSTRAINT analytics_daily_scope_type_check CHECK (scope_type in ('system','branch','user'))");
            } elseif ($driver === 'mysql') {
                DB::statement("ALTER TABLE analytics_daily MODIFY COLUMN scope_type ENUM('system', 'branch', 'user') NOT NULL");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_columns', function (Blueprint $table) {
            //
        });
    }
};
