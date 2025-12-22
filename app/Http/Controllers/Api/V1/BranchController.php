<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\DailyEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BranchController extends ApiController
{
    public function index(Request $request)
    {
        $this->requirePermission('ViewAny:Branch');

        $query = Branch::query()->with('manager')->withCount('employees');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('city')) {
            $query->where('city', $request->string('city'));
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->string('manager_id'));
        }

        $paginator = $query->paginate($this->perPage());

        $branchIds = $paginator->getCollection()->pluck('id')->all();
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $todaySales = DailyEntry::query()
            ->select('branch_id', DB::raw('COALESCE(SUM(sales), 0) as total_sales'))
            ->whereIn('branch_id', $branchIds)
            ->whereDate('date', $today)
            ->groupBy('branch_id')
            ->pluck('total_sales', 'branch_id');

        $monthSales = DailyEntry::query()
            ->select('branch_id', DB::raw('COALESCE(SUM(sales), 0) as total_sales'))
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('date', [$monthStart, $today])
            ->groupBy('branch_id')
            ->pluck('total_sales', 'branch_id');

        $items = $paginator->getCollection()->map(function (Branch $branch) use ($todaySales, $monthSales) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'city' => $branch->city,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'manager' => $branch->manager ? [
                    'id' => $branch->manager->id,
                    'name' => $branch->manager->name,
                ] : null,
                'status' => $branch->status,
                'employees_count' => $branch->employees_count,
                'stats' => [
                    'today_sales' => (float) ($todaySales[$branch->id] ?? 0),
                    'month_sales' => (float) ($monthSales[$branch->id] ?? 0),
                ],
                'created_at' => $branch->created_at?->toIso8601String(),
            ];
        })->values()->all();

        return $this->paginated($paginator, $items);
    }

    public function store(Request $request)
    {
        $this->requirePermission('Create:Branch');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', 'unique:branches,code'],
            'city' => ['nullable', 'string', 'max:50'],
            'region' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'manager_id' => ['nullable', 'uuid', 'exists:users,id'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i'],
            'working_days' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'maintenance'])],
        ]);

        $branch = Branch::create(array_merge($data, [
            'status' => $data['status'] ?? 'active',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        return $this->success([
            'id' => $branch->id,
            'name' => $branch->name,
            'code' => $branch->code,
            'created_at' => $branch->created_at?->toIso8601String(),
        ], 'تم إنشاء الفرع بنجاح', 201);
    }

    public function show(Branch $branch)
    {
        $this->requirePermission('View:Branch');

        $branch->load(['manager', 'employees']);

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $todayStats = DailyEntry::query()
            ->where('branch_id', $branch->id)
            ->whereDate('date', $today)
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales, COUNT(*) as entries_count')
            ->first();

        $monthStats = DailyEntry::query()
            ->where('branch_id', $branch->id)
            ->whereBetween('date', [$monthStart, $today])
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales, COUNT(*) as entries_count')
            ->first();

        $activeEmployees = $branch->employees->where('status', 'active')->count();

        return $this->success([
            'id' => $branch->id,
            'name' => $branch->name,
            'code' => $branch->code,
            'address' => $branch->address,
            'city' => $branch->city,
            'region' => $branch->region,
            'phone' => $branch->phone,
            'email' => $branch->email,
            'manager' => $branch->manager ? [
                'id' => $branch->manager->id,
                'name' => $branch->manager->name,
                'phone' => $branch->manager->phone,
            ] : null,
            'status' => $branch->status,
            'opening_time' => $branch->opening_time,
            'closing_time' => $branch->closing_time,
            'working_days' => $branch->working_days,
            'employees' => $branch->employees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'role' => $employee->role,
                    'status' => $employee->status,
                ];
            })->values()->all(),
            'stats' => [
                'total_employees' => $branch->employees->count(),
                'active_employees' => $activeEmployees,
                'today_sales' => (float) ($todayStats->total_sales ?? 0),
                'today_entries' => (int) ($todayStats->entries_count ?? 0),
                'month_sales' => (float) ($monthStats->total_sales ?? 0),
                'month_entries' => (int) ($monthStats->entries_count ?? 0),
            ],
            'created_at' => $branch->created_at?->toIso8601String(),
            'updated_at' => $branch->updated_at?->toIso8601String(),
        ]);
    }
}
