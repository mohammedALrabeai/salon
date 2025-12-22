<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Document;
use App\Models\Employee;
use App\Models\LedgerEntry;
use App\Models\DailyEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends ApiController
{
    public function index(Request $request)
    {
        $this->requirePermission('ViewAny:Employee');

        $query = Employee::query()->with('branch');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->string('branch_id'));
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($this->perPage());
        $employeeIds = $paginator->getCollection()->pluck('id')->all();

        $totals = DailyEntry::query()
            ->select('employee_id', DB::raw('COALESCE(SUM(sales), 0) as total_sales'), DB::raw('COALESCE(SUM(commission), 0) as total_commission'), DB::raw('COUNT(*) as entries_count'))
            ->whereIn('employee_id', $employeeIds)
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        $items = $paginator->getCollection()->map(function (Employee $employee) use ($totals) {
            $stats = $totals->get($employee->id);

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'phone' => $employee->phone,
                'role' => $employee->role,
                'branch' => $employee->branch ? [
                    'id' => $employee->branch->id,
                    'name' => $employee->branch->name,
                ] : null,
                'commission_rate' => (float) $employee->commission_rate,
                'hire_date' => $employee->hire_date?->toDateString(),
                'status' => $employee->status,
                'stats' => [
                    'total_sales' => (float) ($stats->total_sales ?? 0),
                    'total_commission' => (float) ($stats->total_commission ?? 0),
                    'total_entries' => (int) ($stats->entries_count ?? 0),
                ],
            ];
        })->values()->all();

        return $this->paginated($paginator, $items);
    }

    public function store(Request $request)
    {
        $this->requirePermission('Create:Employee');

        $data = $request->validate([
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'unique:employees,phone'],
            'email' => ['nullable', 'email', 'max:100'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'passport_number' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in(['barber', 'manager', 'receptionist', 'other'])],
            'hire_date' => ['required', 'date'],
            'commission_rate' => ['nullable', 'numeric'],
            'commission_type' => ['nullable', 'string', Rule::in(['percentage', 'fixed', 'tiered'])],
            'base_salary' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'on_leave', 'suspended'])],
        ]);

        $employee = Employee::create(array_merge($data, [
            'status' => $data['status'] ?? 'active',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        return $this->success([
            'id' => $employee->id,
            'name' => $employee->name,
            'phone' => $employee->phone,
            'role' => $employee->role,
            'created_at' => $employee->created_at?->toIso8601String(),
        ], 'تم إنشاء الموظف بنجاح', 201);
    }

    public function show(Employee $employee)
    {
        $this->requirePermission('View:Employee');

        $employee->load('branch');

        $totals = DailyEntry::query()
            ->where('employee_id', $employee->id)
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales, COALESCE(SUM(commission), 0) as total_commission, COALESCE(SUM(bonus), 0) as total_bonus, COUNT(*) as entries_count')
            ->first();

        $workingDays = (int) ($totals->entries_count ?? 0);
        $avgDailySales = $workingDays > 0 ? ((float) $totals->total_sales) / $workingDays : 0.0;

        $ledgerTotals = LedgerEntry::query()
            ->where('party_type', 'employee')
            ->where('party_id', $employee->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) as total_debit")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) as total_credit")
            ->first();

        $ledgerBalance = (float) ($ledgerTotals->total_credit ?? 0) - (float) ($ledgerTotals->total_debit ?? 0);

        $documentsCount = Document::query()
            ->where('owner_type', 'employee')
            ->where('owner_id', $employee->id)
            ->count();

        $documentsExpiringSoon = Document::query()
            ->where('owner_type', 'employee')
            ->where('owner_id', $employee->id)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->count();

        return $this->success([
            'id' => $employee->id,
            'name' => $employee->name,
            'phone' => $employee->phone,
            'email' => $employee->email,
            'national_id' => $employee->national_id,
            'role' => $employee->role,
            'branch' => $employee->branch ? [
                'id' => $employee->branch->id,
                'name' => $employee->branch->name,
                'code' => $employee->branch->code,
            ] : null,
            'hire_date' => $employee->hire_date?->toDateString(),
            'commission_rate' => (float) $employee->commission_rate,
            'commission_type' => $employee->commission_type,
            'base_salary' => (float) $employee->base_salary,
            'status' => $employee->status,
            'avatar_url' => $employee->avatar_url,
            'stats' => [
                'total_sales' => (float) ($totals->total_sales ?? 0),
                'total_commission' => (float) ($totals->total_commission ?? 0),
                'total_bonus' => (float) ($totals->total_bonus ?? 0),
                'total_entries' => (int) ($totals->entries_count ?? 0),
                'avg_daily_sales' => $avgDailySales,
                'ledger_balance' => $ledgerBalance,
            ],
            'documents_count' => $documentsCount,
            'documents_expiring_soon' => $documentsExpiringSoon,
            'created_at' => $employee->created_at?->toIso8601String(),
        ]);
    }
}
