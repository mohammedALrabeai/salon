<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\DailyEntry;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends ApiController
{
    public function index(Request $request)
    {
        $this->requirePermission('ViewAny:User');

        $query = User::query()->with('branch');

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
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->string('sort_by', 'created_at');
        $sortOrder = $request->string('sort_order', 'desc');
        $allowedSorts = ['created_at', 'name', 'phone'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');

        $paginator = $query->paginate($this->perPage());

        $items = $paginator->getCollection()->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'branch' => $user->branch ? [
                    'id' => $user->branch->id,
                    'name' => $user->branch->name,
                ] : null,
                'status' => $user->status,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ];
        })->values()->all();

        return $this->paginated($paginator, $items);
    }

    public function store(Request $request)
    {
        $this->requirePermission('Create:User');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in([
                'super_admin',
                'owner',
                'manager',
                'accountant',
                'barber',
                'doc_supervisor',
                'receptionist',
                'auditor',
            ])],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'role' => $data['role'],
            'branch_id' => $data['branch_id'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
            'created_at' => $user->created_at?->toIso8601String(),
        ], 'تم إنشاء المستخدم بنجاح', 201);
    }

    public function show(User $user)
    {
        $this->requirePermission('View:User');

        $user->load('branch');

        $stats = [
            'total_entries' => 0,
            'total_sales' => 0.0,
            'total_commission' => 0.0,
        ];
        $employee = Employee::query()->where('phone', $user->phone)->first();

        if ($employee) {
            $totals = DailyEntry::query()
                ->where('employee_id', $employee->id)
                ->selectRaw('COUNT(*) as total_entries, COALESCE(SUM(sales), 0) as total_sales, COALESCE(SUM(commission), 0) as total_commission')
                ->first();

            $stats = [
                'total_entries' => (int) ($totals->total_entries ?? 0),
                'total_sales' => (float) ($totals->total_sales ?? 0),
                'total_commission' => (float) ($totals->total_commission ?? 0),
            ];
        }

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'branch' => $user->branch ? [
                'id' => $user->branch->id,
                'name' => $user->branch->name,
                'code' => $user->branch->code,
                'city' => $user->branch->city,
            ] : null,
            'avatar_url' => $user->avatar_url,
            'status' => $user->status,
            'settings' => $user->settings ?? [],
            'stats' => $stats,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->requirePermission('Update:User');

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['sometimes', 'string', Rule::in([
                'super_admin',
                'owner',
                'manager',
                'accountant',
                'barber',
                'doc_supervisor',
                'receptionist',
                'auditor',
            ])],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
            'avatar_url' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'preferences' => ['nullable', 'array'],
        ]);

        $user->forceFill($data);
        $user->updated_by = $request->user()->id;
        $user->save();

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'updated_at' => $user->updated_at?->toIso8601String(),
        ], 'تم تحديث المستخدم بنجاح');
    }

    public function destroy(User $user)
    {
        $this->requirePermission('Delete:User');

        $user->delete();

        return $this->success(null, 'تم حذف المستخدم بنجاح');
    }

    public function changePassword(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            $this->requirePermission('Update:User');
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->password_hash)) {
            return $this->error('INVALID_CREDENTIALS', 'كلمة المرور الحالية غير صحيحة', 401);
        }

        $user->forceFill([
            'password_hash' => Hash::make($data['new_password']),
            'updated_by' => $request->user()->id,
        ])->save();

        return $this->success(null, 'تم تغيير كلمة المرور بنجاح');
    }
}
