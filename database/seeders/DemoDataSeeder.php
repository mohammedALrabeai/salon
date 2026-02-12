<?php

namespace Database\Seeders;

use App\Models\AdvanceRequest;
use App\Models\AnalyticsDaily;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DailyEntry;
use App\Models\DayClosure;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\Employee;
use App\Models\LedgerEntry;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $admin = User::query()->where('role', 'super_admin')->first() ?? User::query()->first();
        $adminId = $admin?->id;

        $roleUsers = [
            [
                'role' => 'manager',
                'name' => 'Branch Manager',
                'phone' => '0500000001',
                'email' => 'manager@example.com',
            ],
            [
                'role' => 'accountant',
                'name' => 'Accountant User',
                'phone' => '0500000002',
                'email' => 'accountant@example.com',
            ],
            [
                'role' => 'receptionist',
                'name' => 'Reception User',
                'phone' => '0500000003',
                'email' => 'reception@example.com',
            ],
            [
                'role' => 'barber',
                'name' => 'Senior Barber',
                'phone' => '0500000004',
                'email' => 'barber@example.com',
            ],
            [
                'role' => 'doc_supervisor',
                'name' => 'Document Supervisor',
                'phone' => '0500000005',
                'email' => 'docs@example.com',
            ],
            [
                'role' => 'auditor',
                'name' => 'Audit User',
                'phone' => '0500000006',
                'email' => 'audit@example.com',
            ],
        ];

        $usersByRole = [];

        foreach ($roleUsers as $data) {
            $user = User::updateOrCreate(
                ['phone' => $data['phone']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password_hash' => Hash::make('12345678'),
                    'role' => $data['role'],
                    'status' => 'active',
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );

            if (Role::query()->where('name', $data['role'])->exists()) {
                $user->syncRoles([$data['role']]);
            }

            $usersByRole[$data['role']] = $user;
        }

        $branchMain = Branch::updateOrCreate(
            ['code' => 'BR-001'],
            [
                'name' => 'Riyadh Main',
                'address' => 'King Fahd Rd',
                'city' => 'Riyadh',
                'region' => 'Riyadh',
                'country' => 'Saudi Arabia',
                'postal_code' => '12211',
                'phone' => '0112000001',
                'email' => 'riyadh-main@example.com',
                'manager_id' => $usersByRole['manager']?->id,
                'status' => 'active',
                'opening_time' => '10:00',
                'closing_time' => '23:00',
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'settings' => ['timezone' => 'Asia/Riyadh'],
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $branchNorth = Branch::updateOrCreate(
            ['code' => 'BR-002'],
            [
                'name' => 'Riyadh North',
                'address' => 'Anas Ibn Malik Rd',
                'city' => 'Riyadh',
                'region' => 'Riyadh',
                'country' => 'Saudi Arabia',
                'postal_code' => '13321',
                'phone' => '0112000002',
                'email' => 'riyadh-north@example.com',
                'manager_id' => $usersByRole['manager']?->id,
                'status' => 'active',
                'opening_time' => '10:00',
                'closing_time' => '22:30',
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'settings' => ['timezone' => 'Asia/Riyadh'],
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        if ($usersByRole['manager'] ?? false) {
            $usersByRole['manager']->update(['branch_id' => $branchMain->id]);
        }

        if ($usersByRole['accountant'] ?? false) {
            $usersByRole['accountant']->update(['branch_id' => $branchMain->id]);
        }

        if ($usersByRole['receptionist'] ?? false) {
            $usersByRole['receptionist']->update(['branch_id' => $branchNorth->id]);
        }

        $employeeAhmed = Employee::updateOrCreate(
            ['phone' => '0501111111'],
            [
                'branch_id' => $branchMain->id,
                'name' => 'Ahmed Ali',
                'email' => 'ahmed.ali@example.com',
                'password_hash' => Hash::make('12345678'),
                'role' => 'barber',
                'hire_date' => $now->copy()->subMonths(18)->toDateString(),
                'employment_type' => 'full_time',
                'commission_rate' => 45,
                'commission_type' => 'percentage',
                'base_salary' => 2500,
                'status' => 'active',
                'skills' => ['fade', 'beard', 'color'],
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $employeeSami = Employee::updateOrCreate(
            ['phone' => '0501111112'],
            [
                'branch_id' => $branchMain->id,
                'name' => 'Sami Noor',
                'email' => 'sami.noor@example.com',
                'password_hash' => Hash::make('12345678'),
                'role' => 'barber',
                'hire_date' => $now->copy()->subMonths(8)->toDateString(),
                'employment_type' => 'full_time',
                'commission_rate' => 40,
                'commission_type' => 'percentage',
                'base_salary' => 2200,
                'status' => 'active',
                'skills' => ['classic', 'beard'],
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $employeeMona = Employee::updateOrCreate(
            ['phone' => '0501111113'],
            [
                'branch_id' => $branchMain->id,
                'name' => 'Mona Hadi',
                'email' => 'mona.hadi@example.com',
                'password_hash' => Hash::make('12345678'),
                'role' => 'receptionist',
                'hire_date' => $now->copy()->subMonths(14)->toDateString(),
                'employment_type' => 'full_time',
                'commission_rate' => 0,
                'commission_type' => 'fixed',
                'base_salary' => 3200,
                'status' => 'active',
                'skills' => ['booking', 'customer_care'],
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $employeeNasser = Employee::updateOrCreate(
            ['phone' => '0501111114'],
            [
                'branch_id' => $branchNorth->id,
                'name' => 'Nasser Omar',
                'email' => 'nasser.omar@example.com',
                'password_hash' => Hash::make('12345678'),
                'role' => 'barber',
                'hire_date' => $now->copy()->subMonths(11)->toDateString(),
                'employment_type' => 'full_time',
                'commission_rate' => 35,
                'commission_type' => 'percentage',
                'base_salary' => 2300,
                'status' => 'active',
                'skills' => ['fade', 'kids'],
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $entryDates = [
            $now->copy()->subDays(2)->toDateString(),
            $now->copy()->subDay()->toDateString(),
            $now->copy()->toDateString(),
        ];

        $createEntry = function (Employee $employee, string $date, float $sales, float $cash, float $expense, int $transactions, bool $locked = false) use ($adminId) {
            $commissionRate = (float) ($employee->commission_rate ?? 0);
            $commission = round($sales * $commissionRate / 100, 2);
            $bonus = $sales >= 1200 ? 75 : 0;

            DailyEntry::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $date,
                ],
                [
                    'branch_id' => $employee->branch_id,
                    'sales' => $sales,
                    'cash' => $cash,
                    'expense' => $expense,
                    'commission' => $commission,
                    'commission_rate' => $commissionRate,
                    'bonus' => $bonus,
                    'bonus_reason' => $bonus > 0 ? 'High sales bonus' : null,
                    'transactions_count' => $transactions,
                    'source' => 'web',
                    'is_locked' => $locked,
                    'locked_at' => $locked ? now() : null,
                    'locked_by' => $locked ? $adminId : null,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );
        };

        $createEntry($employeeAhmed, $entryDates[0], 1350, 180, 120, 18, true);
        $createEntry($employeeAhmed, $entryDates[1], 980, 120, 80, 14);
        $createEntry($employeeSami, $entryDates[1], 760, 90, 60, 11);
        $createEntry($employeeSami, $entryDates[2], 1120, 140, 100, 16);
        $createEntry($employeeNasser, $entryDates[1], 690, 80, 40, 9);

        $closureDate = $entryDates[1];
        $branchEntries = DailyEntry::query()
            ->where('branch_id', $branchMain->id)
            ->where('date', $closureDate)
            ->get();

        if ($branchEntries->isNotEmpty()) {
            $totalSales = $branchEntries->sum('sales');
            $totalCash = $branchEntries->sum('cash');
            $totalExpense = $branchEntries->sum('expense');
            $totalNet = $totalSales - $totalCash - $totalExpense;
            $totalCommission = $branchEntries->sum('commission');
            $totalBonus = $branchEntries->sum('bonus');
            $entriesCount = $branchEntries->count();
            $employeesCount = $branchEntries->pluck('employee_id')->unique()->count();

            DayClosure::updateOrCreate(
                [
                    'branch_id' => $branchMain->id,
                    'date' => $closureDate,
                ],
                [
                    'total_sales' => $totalSales,
                    'total_cash' => $totalCash,
                    'total_expense' => $totalExpense,
                    'total_net' => $totalNet,
                    'total_commission' => $totalCommission,
                    'total_bonus' => $totalBonus,
                    'entries_count' => $entriesCount,
                    'employees_count' => $employeesCount,
                    'closed_by' => $adminId,
                    'closed_at' => $now,
                    'notes' => 'Auto closure for demo data',
                ]
            );
        }

        $ledgerExpense = LedgerEntry::updateOrCreate(
            [
                'reference_type' => 'closure',
                'reference_id' => $branchMain->id,
                'date' => $closureDate,
                'type' => 'debit',
            ],
            [
                'party_type' => 'branch',
                'party_id' => $branchMain->id,
                'amount' => 320.00,
                'description' => 'Daily supplies and utilities',
                'category' => 'expenses',
                'source' => 'closure',
                'payment_method' => 'cash',
                'status' => 'confirmed',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $ledgerAdvance = LedgerEntry::updateOrCreate(
            [
                'reference_type' => 'advance_request',
                'reference_id' => $employeeSami->id,
                'date' => $closureDate,
                'type' => 'debit',
            ],
            [
                'party_type' => 'employee',
                'party_id' => $employeeSami->id,
                'amount' => 300.00,
                'description' => 'Employee advance payment',
                'category' => 'advance',
                'source' => 'advance_request',
                'payment_method' => 'cash',
                'status' => 'confirmed',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $pendingRequestedAt = $now->copy()->subDay();
        $approvedRequestedAt = $now->copy()->subDays(2);

        AdvanceRequest::updateOrCreate(
            [
                'employee_id' => $employeeAhmed->id,
                'requested_at' => $pendingRequestedAt,
            ],
            [
                'branch_id' => $branchMain->id,
                'amount' => 250.00,
                'reason' => 'Personal expense',
                'status' => 'pending',
            ]
        );

        AdvanceRequest::updateOrCreate(
            [
                'employee_id' => $employeeSami->id,
                'requested_at' => $approvedRequestedAt,
            ],
            [
                'branch_id' => $branchMain->id,
                'amount' => 300.00,
                'reason' => 'Emergency request',
                'status' => 'approved',
                'processed_at' => $now,
                'processed_by' => $usersByRole['manager']?->id ?? $adminId,
                'decision_notes' => 'Approved for payroll deduction',
                'payment_date' => $now->copy()->toDateString(),
                'payment_method' => 'cash',
                'ledger_entry_id' => $ledgerAdvance->id,
            ]
        );

        $branchDocument = Document::updateOrCreate(
            [
                'owner_type' => 'branch',
                'owner_id' => $branchMain->id,
                'type' => 'license',
                'number' => 'LIC-1001',
            ],
            [
                'title' => 'Branch Commercial License',
                'issue_date' => $now->copy()->subMonths(10)->toDateString(),
                'expiry_date' => $now->copy()->addMonths(3)->toDateString(),
                'notify_before_days' => 30,
                'notes' => 'Renew in Q2',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $employeeDocument = Document::updateOrCreate(
            [
                'owner_type' => 'employee',
                'owner_id' => $employeeAhmed->id,
                'type' => 'id',
                'number' => 'EMP-1001',
            ],
            [
                'title' => 'Employee ID',
                'issue_date' => $now->copy()->subMonths(24)->toDateString(),
                'expiry_date' => $now->copy()->subMonths(1)->toDateString(),
                'notify_before_days' => 15,
                'notes' => 'Needs renewal',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        DocumentFile::updateOrCreate(
            [
                'document_id' => $branchDocument->id,
                'version' => 1,
            ],
            [
                'name' => 'license.pdf',
                'size' => 245000,
                'mime_type' => 'application/pdf',
                'file_url' => 'https://example.com/docs/license.pdf',
                'storage_provider' => 'local',
                'is_current' => true,
                'uploaded_at' => $now,
                'uploaded_by' => $adminId,
            ]
        );

        DocumentFile::updateOrCreate(
            [
                'document_id' => $employeeDocument->id,
                'version' => 1,
            ],
            [
                'name' => 'employee-id.pdf',
                'size' => 98000,
                'mime_type' => 'application/pdf',
                'file_url' => 'https://example.com/docs/employee-id.pdf',
                'storage_provider' => 'local',
                'is_current' => true,
                'uploaded_at' => $now,
                'uploaded_by' => $adminId,
            ]
        );

        Notification::updateOrCreate(
            [
                'title' => 'Welcome to the dashboard',
                'target_type' => 'all',
            ],
            [
                'type' => 'system',
                'target_id' => null,
                'message' => 'Your salon operations are now active in the system.',
                'status' => 'sent',
                'priority' => 'normal',
                'channels' => ['in_app'],
                'sent_at' => $now,
            ]
        );

        if ($adminId) {
            Notification::updateOrCreate(
                [
                    'title' => 'Document expiry reminder',
                    'target_type' => 'user',
                    'target_id' => $adminId,
                ],
                [
                    'type' => 'document_expiry',
                    'message' => 'Employee ID for Ahmed Ali has expired.',
                    'status' => 'pending',
                    'priority' => 'high',
                    'channels' => ['in_app', 'email'],
                    'data' => [
                        'document_id' => $employeeDocument->id,
                        'owner_type' => 'employee',
                    ],
                ]
            );
        }

        AuditLog::updateOrCreate(
            [
                'action' => 'seed_data',
                'entity_type' => 'system',
                'entity_id' => null,
            ],
            [
                'user_id' => $adminId,
                'user_name' => $admin?->name,
                'user_role' => $admin?->role,
                'old_values' => null,
                'new_values' => ['seeded' => true],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'artisan',
                'request_method' => 'CLI',
                'request_url' => 'artisan db:seed',
                'status' => 'success',
                'created_at' => $now,
            ]
        );

        AuditLog::updateOrCreate(
            [
                'action' => 'create',
                'entity_type' => 'employee',
                'entity_id' => $employeeAhmed->id,
            ],
            [
                'user_id' => $adminId,
                'user_name' => $admin?->name,
                'user_role' => $admin?->role,
                'old_values' => null,
                'new_values' => [
                    'name' => $employeeAhmed->name,
                    'branch_id' => $employeeAhmed->branch_id,
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'artisan',
                'request_method' => 'CLI',
                'request_url' => 'artisan db:seed',
                'status' => 'success',
                'created_at' => $now,
            ]
        );

        $systemTotals = DailyEntry::query()
            ->where('date', $closureDate)
            ->get();

        if ($systemTotals->isNotEmpty()) {
            $totalSales = $systemTotals->sum('sales');
            $totalCash = $systemTotals->sum('cash');
            $totalExpense = $systemTotals->sum('expense');
            $totalNet = $totalSales - $totalCash - $totalExpense;
            $totalCommission = $systemTotals->sum('commission');
            $totalBonus = $systemTotals->sum('bonus');
            $entriesCount = $systemTotals->count();
            $employeesCount = $systemTotals->pluck('employee_id')->unique()->count();
            $transactionsCount = (int) $systemTotals->sum('transactions_count');

            AnalyticsDaily::updateOrCreate(
                [
                    'date' => $closureDate,
                    'scope_type' => 'system',
                    'scope_id' => null,
                ],
                [
                    'total_sales' => $totalSales,
                    'total_cash' => $totalCash,
                    'total_expense' => $totalExpense,
                    'total_net' => $totalNet,
                    'total_commission' => $totalCommission,
                    'total_bonus' => $totalBonus,
                    'entries_count' => $entriesCount,
                    'employees_count' => $employeesCount,
                    'transactions_count' => $transactionsCount,
                    'avg_sale_value' => $entriesCount > 0 ? round($totalSales / $entriesCount, 2) : null,
                    'avg_commission_rate' => $entriesCount > 0 ? round(($totalCommission / $totalSales) * 100, 2) : null,
                    'computed_at' => $now,
                ]
            );
        }

        AnalyticsDaily::updateOrCreate(
            [
                'date' => $closureDate,
                'scope_type' => 'branch',
                'scope_id' => $branchMain->id,
            ],
            [
                'total_sales' => $branchEntries->sum('sales'),
                'total_cash' => $branchEntries->sum('cash'),
                'total_expense' => $branchEntries->sum('expense'),
                'total_net' => $branchEntries->sum('sales') - $branchEntries->sum('cash') - $branchEntries->sum('expense'),
                'total_commission' => $branchEntries->sum('commission'),
                'total_bonus' => $branchEntries->sum('bonus'),
                'entries_count' => $branchEntries->count(),
                'employees_count' => $branchEntries->pluck('employee_id')->unique()->count(),
                'transactions_count' => (int) $branchEntries->sum('transactions_count'),
                'avg_sale_value' => $branchEntries->count() > 0 ? round($branchEntries->sum('sales') / $branchEntries->count(), 2) : null,
                'avg_commission_rate' => $branchEntries->sum('sales') > 0 ? round(($branchEntries->sum('commission') / $branchEntries->sum('sales')) * 100, 2) : null,
                'computed_at' => $now,
            ]
        );

        AnalyticsDaily::updateOrCreate(
            [
                'date' => $closureDate,
                'scope_type' => 'employee',
                'scope_id' => $employeeAhmed->id,
            ],
            [
                'total_sales' => $employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('sales'),
                'total_cash' => $employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('cash'),
                'total_expense' => $employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('expense'),
                'total_net' => $employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('sales')
                    - $employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('cash')
                    - $employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('expense'),
                'total_commission' => $employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('commission'),
                'total_bonus' => $employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('bonus'),
                'entries_count' => $employeeAhmed->dailyEntries()->where('date', $closureDate)->count(),
                'employees_count' => 1,
                'transactions_count' => (int) $employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('transactions_count'),
                'avg_sale_value' => $employeeAhmed->dailyEntries()->where('date', $closureDate)->count() > 0
                    ? round($employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('sales') / $employeeAhmed->dailyEntries()->where('date', $closureDate)->count(), 2)
                    : null,
                'avg_commission_rate' => $employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('sales') > 0
                    ? round(($employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('commission') / $employeeAhmed->dailyEntries()->where('date', $closureDate)->sum('sales')) * 100, 2)
                    : null,
                'computed_at' => $now,
            ]
        );
    }
}
