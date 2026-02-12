<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addEmployeeFieldsToUsers();
        $this->updateUserEnums();
        $this->mergeEmployeesIntoUsers();
        $this->updateEmployeeForeignKeys();
    }

    public function down(): void
    {
        // Intentionally left empty to avoid accidental data loss.
    }

    private function addEmployeeFieldsToUsers(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'national_id')) {
                $table->string('national_id', 20)->nullable();
            }
            if (! Schema::hasColumn('users', 'passport_number')) {
                $table->string('passport_number', 20)->nullable();
            }
            if (! Schema::hasColumn('users', 'hire_date')) {
                $table->date('hire_date')->nullable();
            }
            if (! Schema::hasColumn('users', 'termination_date')) {
                $table->date('termination_date')->nullable();
            }
            if (! Schema::hasColumn('users', 'employment_type')) {
                $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'freelance'])->nullable();
            }
            if (! Schema::hasColumn('users', 'commission_rate')) {
                $table->decimal('commission_rate', 5, 2)->nullable();
            }
            if (! Schema::hasColumn('users', 'commission_type')) {
                $table->enum('commission_type', ['percentage', 'fixed', 'tiered'])->nullable();
            }
            if (! Schema::hasColumn('users', 'base_salary')) {
                $table->decimal('base_salary', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('users', 'skills')) {
                $driver = DB::getDriverName();
                if ($driver === 'pgsql') {
                    $table->jsonb('skills')->default('[]');
                } else {
                    $table->json('skills')->default('[]');
                }
            }
        });
    }

    private function updateUserEnums(): void
    {
        $driver = DB::getDriverName();
        $roles = [
            'super_admin',
            'owner',
            'manager',
            'accountant',
            'barber',
            'doc_supervisor',
            'receptionist',
            'auditor',
            'other',
        ];
        $statuses = ['active', 'inactive', 'suspended', 'on_leave'];

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_status_check");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role in ('".implode("','", $roles)."'))");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status in ('".implode("','", $statuses)."'))");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('".implode("','", $roles)."') NOT NULL");
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('".implode("','", $statuses)."') NOT NULL DEFAULT 'active'");
        }
    }

    private function mergeEmployeesIntoUsers(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        $employees = DB::table('employees')->get();
        $idMap = [];

        foreach ($employees as $employee) {
            $user = DB::table('users')->where('phone', $employee->phone)->first();

            if ($user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'branch_id' => $employee->branch_id,
                        'name' => $employee->name,
                        'email' => $employee->email ?? $user->email,
                        'role' => $employee->role,
                        'status' => $employee->status,
                        'national_id' => $employee->national_id,
                        'passport_number' => $employee->passport_number,
                        'hire_date' => $employee->hire_date,
                        'termination_date' => $employee->termination_date,
                        'employment_type' => $employee->employment_type,
                        'commission_rate' => $employee->commission_rate,
                        'commission_type' => $employee->commission_type,
                        'base_salary' => $employee->base_salary,
                        'avatar_url' => $employee->avatar_url,
                        'bio' => $employee->bio,
                        'skills' => $employee->skills ?? '[]',
                        'created_by' => $employee->created_by,
                        'updated_by' => $employee->updated_by,
                        'created_at' => $employee->created_at,
                        'updated_at' => $employee->updated_at,
                        'deleted_at' => $employee->deleted_at,
                    ]);

                $idMap[$employee->id] = $user->id;
                continue;
            }

            $userId = $employee->id;
            DB::table('users')->insert([
                'id' => $userId,
                'name' => $employee->name,
                'phone' => $employee->phone,
                'email' => $employee->email,
                'password_hash' => Hash::make('12345678'),
                'role' => $employee->role,
                'branch_id' => $employee->branch_id,
                'status' => $employee->status,
                'national_id' => $employee->national_id,
                'passport_number' => $employee->passport_number,
                'hire_date' => $employee->hire_date,
                'termination_date' => $employee->termination_date,
                'employment_type' => $employee->employment_type,
                'commission_rate' => $employee->commission_rate,
                'commission_type' => $employee->commission_type,
                'base_salary' => $employee->base_salary,
                'avatar_url' => $employee->avatar_url,
                'bio' => $employee->bio,
                'skills' => $employee->skills ?? '[]',
                'created_by' => $employee->created_by,
                'updated_by' => $employee->updated_by,
                'created_at' => $employee->created_at ?? now(),
                'updated_at' => $employee->updated_at ?? now(),
                'deleted_at' => $employee->deleted_at,
            ]);

            $idMap[$employee->id] = $userId;
        }

        foreach ($idMap as $employeeId => $userId) {
            if ($employeeId === $userId) {
                continue;
            }

            if (Schema::hasTable('daily_entries')) {
                DB::table('daily_entries')
                    ->where('employee_id', $employeeId)
                    ->update(['employee_id' => $userId]);
            }

            if (Schema::hasTable('advance_requests')) {
                DB::table('advance_requests')
                    ->where('employee_id', $employeeId)
                    ->update(['employee_id' => $userId]);
            }

            if (Schema::hasTable('documents')) {
                DB::table('documents')
                    ->where('owner_type', 'employee')
                    ->where('owner_id', $employeeId)
                    ->update(['owner_id' => $userId]);
            }

            if (Schema::hasTable('ledger_entries')) {
                DB::table('ledger_entries')
                    ->where('party_type', 'employee')
                    ->where('party_id', $employeeId)
                    ->update(['party_id' => $userId]);
            }

            if (Schema::hasTable('analytics_daily')) {
                DB::table('analytics_daily')
                    ->where('scope_type', 'employee')
                    ->where('scope_id', $employeeId)
                    ->update(['scope_id' => $userId]);
            }
        }
    }

    private function updateEmployeeForeignKeys(): void
    {
        if (Schema::hasTable('daily_entries')) {
            Schema::table('daily_entries', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
            });
        }

        if (Schema::hasTable('advance_requests')) {
            Schema::table('advance_requests', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::drop('employees');
        }

        if (Schema::hasTable('daily_entries')) {
            Schema::table('daily_entries', function (Blueprint $table) {
                $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('advance_requests')) {
            Schema::table('advance_requests', function (Blueprint $table) {
                $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }
};
