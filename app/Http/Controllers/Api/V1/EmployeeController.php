<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\DailyEntry;
use App\Models\Document;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends ApiController
{
    public function index(Request $request)
    {
        $this->requireAnyPermission(['ViewAny:Employee', 'ViewAny:User']);

        $query = User::query()
            ->with('branch')
            ->whereIn('role', User::employeeRoles());

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->string('branch_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortOrder = $request->string('sort_order', 'desc')->toString();
        $allowedSorts = ['created_at', 'name', 'phone', 'hire_date', 'commission_rate'];

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');

        $paginator = $query->paginate($this->perPage());

        $items = $paginator->getCollection()
            ->map(fn(User $employee) => $this->serializeEmployee($employee))
            ->values()
            ->all();

        return $this->paginated($paginator, $items);
    }

    public function show(User $employee)
    {
        $this->ensureEmployee($employee);
        $this->requireAnyPermissionOrSelf(['View:Employee', 'View:User'], $employee->id);

        $employee->load('branch');

        return $this->success($this->serializeEmployeeDetail($employee));
    }

    public function store(Request $request)
    {
        $this->requireAnyPermission(['Create:Employee', 'Create:User']);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:100', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'role' => ['required', 'string', Rule::in(User::employeeRoles())],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended', 'on_leave'])],
            'hire_date' => ['required', 'date'],
            'commission_rate' => ['nullable', 'numeric', 'min:0'],
            'commission_type' => ['nullable', 'string', Rule::in(['percentage', 'fixed', 'tiered'])],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'avatar_url' => ['nullable', 'string'],
        ]);

        $employee = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password_hash' => Hash::make($data['password'] ?? $data['phone']),
            'branch_id' => $data['branch_id'],
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
            'hire_date' => $data['hire_date'],
            'commission_rate' => $data['commission_rate'] ?? null,
            'commission_type' => $data['commission_type'] ?? 'percentage',
            'base_salary' => $data['base_salary'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'avatar_url' => $data['avatar_url'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $employee->load('branch');

        $message = empty($data['password'])
            ? 'تم إنشاء الموظف بنجاح. كلمة المرور الافتراضية هي رقم الجوال'
            : 'تم إنشاء الموظف بنجاح';

        return $this->success($this->serializeEmployee($employee), $message, 201);
    }

    public function update(Request $request, User $employee)
    {
        $this->ensureEmployee($employee);
        $this->requireAnyPermission(['Update:Employee', 'Update:User']);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($employee->id)],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($employee->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'branch_id' => ['sometimes', 'uuid', 'exists:branches,id'],
            'role' => ['sometimes', 'string', Rule::in(User::employeeRoles())],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended', 'on_leave'])],
            'hire_date' => ['sometimes', 'date'],
            'commission_rate' => ['nullable', 'numeric', 'min:0'],
            'commission_type' => ['nullable', 'string', Rule::in(['percentage', 'fixed', 'tiered'])],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'avatar_url' => ['nullable', 'string'],
        ]);

        if (!empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
        }

        unset($data['password']);

        $employee->forceFill($data);
        $employee->updated_by = $request->user()->id;
        $employee->save();
        $employee->load('branch');

        return $this->success($this->serializeEmployee($employee), 'تم تحديث الموظف بنجاح');
    }

    public function destroy(User $employee)
    {
        $this->ensureEmployee($employee);
        $this->requireAnyPermission(['Delete:Employee', 'Delete:User']);

        $employee->delete();

        return $this->success(null, 'تم حذف الموظف بنجاح');
    }

    private function ensureEmployee(User $employee): void
    {
        if (!in_array($employee->role, User::employeeRoles(), true)) {
            abort(404);
        }
    }

    private function serializeEmployee(User $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'phone' => $employee->phone,
            'email' => $employee->email,
            'role' => $employee->role,
            'branch_id' => $employee->branch_id,
            'branch' => $employee->branch ? [
                'id' => $employee->branch->id,
                'name' => $employee->branch->name,
            ] : null,
            'status' => $employee->status,
            'hire_date' => $employee->hire_date?->toDateString(),
            'commission_rate' => $employee->commission_rate !== null ? (float) $employee->commission_rate : null,
            'commission_type' => $employee->commission_type,
            'base_salary' => $employee->base_salary !== null ? (float) $employee->base_salary : null,
            'national_id' => $employee->national_id,
            'avatar_url' => $employee->avatar_url,
            'created_at' => $employee->created_at?->toIso8601String(),
            'updated_at' => $employee->updated_at?->toIso8601String(),
        ];
    }

    private function serializeEmployeeDetail(User $employee): array
    {
        $entryStats = DailyEntry::query()
            ->where('user_id', $employee->id)
            ->selectRaw('COUNT(*) as total_entries')
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(commission), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(bonus), 0) as total_bonus')
            ->first();

        $documentsCount = Document::query()
            ->where('owner_type', 'user')
            ->where('owner_id', $employee->id)
            ->count();

        $documentsExpiringSoon = Document::query()
            ->where('owner_type', 'user')
            ->where('owner_id', $employee->id)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(60))
            ->count();

        $ledgerTotals = LedgerEntry::query()
            ->where('party_type', 'user')
            ->where('party_id', $employee->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) as total_debit")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) as total_credit")
            ->first();

        $balance = (float) ($ledgerTotals->total_credit ?? 0) - (float) ($ledgerTotals->total_debit ?? 0);
        $totalEntries = (int) ($entryStats->total_entries ?? 0);

        return array_merge($this->serializeEmployee($employee), [
            'stats' => [
                'total_sales' => (float) ($entryStats->total_sales ?? 0),
                'total_commission' => (float) ($entryStats->total_commission ?? 0),
                'total_bonus' => (float) ($entryStats->total_bonus ?? 0),
                'total_entries' => $totalEntries,
                'avg_daily_sales' => $totalEntries > 0
                    ? round((float) ($entryStats->total_sales ?? 0) / $totalEntries, 2)
                    : 0.0,
                'ledger_balance' => $balance,
            ],
            'documents_count' => $documentsCount,
            'documents_expiring_soon' => $documentsExpiringSoon,
        ]);
    }
}
