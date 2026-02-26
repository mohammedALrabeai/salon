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

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');
        }

        // 1. Rename columns
        if (Schema::hasTable('daily_entries') && Schema::hasColumn('daily_entries', 'employee_id')) {
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

        if (Schema::hasTable('advance_requests') && Schema::hasColumn('advance_requests', 'employee_id')) {
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

        // 2. Update ENUM definitions FIRST, then update data.
        // For SQLite, Schema::table(... change()) requires doctrine/dbal. 
        // If it throws errors on sqlite for enums, we'll recreate the check constraint or just change it to string, update, and back.

        if (Schema::hasTable('documents')) {
            if ($driver === 'sqlite') {
                // To bypass check constraints on SQLite, we convert the ENUM column to a standard string.
                // Laravel's Schema builder will rebuild the table automatically while preserving data.
                Schema::table('documents', function (Blueprint $table) {
                    $table->string('owner_type')->change();
                });
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_owner_type_check");
                DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_owner_type_check CHECK (owner_type in ('user','branch','company'))");
            } elseif ($driver === 'mysql') {
                DB::statement("ALTER TABLE documents MODIFY COLUMN owner_type ENUM('user', 'branch', 'company') NOT NULL");
            }

            DB::table('documents')
                ->where('owner_type', 'employee')
                ->update(['owner_type' => 'user']);
        }

        if (Schema::hasTable('ledger_entries')) {
            if ($driver === 'sqlite') {
                Schema::table('ledger_entries', function (Blueprint $table) {
                    $table->string('party_type')->change();
                });
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE ledger_entries DROP CONSTRAINT IF EXISTS ledger_entries_party_type_check");
                DB::statement("ALTER TABLE ledger_entries ADD CONSTRAINT ledger_entries_party_type_check CHECK (party_type in ('user','branch','supplier','customer'))");
            } elseif ($driver === 'mysql') {
                DB::statement("ALTER TABLE ledger_entries MODIFY COLUMN party_type ENUM('user', 'branch', 'supplier', 'customer') NOT NULL");
            }

            DB::table('ledger_entries')
                ->where('party_type', 'employee')
                ->update(['party_type' => 'user']);
        }

        if (Schema::hasTable('analytics_daily')) {
            if ($driver === 'sqlite') {
                Schema::table('analytics_daily', function (Blueprint $table) {
                    $table->string('scope_type')->change();
                });
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE analytics_daily DROP CONSTRAINT IF EXISTS analytics_daily_scope_type_check");
                DB::statement("ALTER TABLE analytics_daily ADD CONSTRAINT analytics_daily_scope_type_check CHECK (scope_type in ('system','branch','user'))");
            } elseif ($driver === 'mysql') {
                DB::statement("ALTER TABLE analytics_daily MODIFY COLUMN scope_type ENUM('system', 'branch', 'user') NOT NULL");
            }

            DB::table('analytics_daily')
                ->where('scope_type', 'employee')
                ->update(['scope_type' => 'user']);
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON;');
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
