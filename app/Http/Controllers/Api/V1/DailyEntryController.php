<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\DailyEntry;
use App\Models\DayClosure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DailyEntryController extends ApiController
{
    public function index(Request $request)
    {
        $this->requirePermission('ViewAny:DailyEntry');

        $query = DailyEntry::query()->with(['employee', 'branch']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->string('employee_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->string('branch_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        if ($request->filled('is_locked')) {
            $query->where('is_locked', $request->boolean('is_locked'));
        }

        $paginator = $query->orderByDesc('date')->paginate($this->perPage());

        $summary = (clone $query)
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(cash), 0) as total_cash')
            ->selectRaw('COALESCE(SUM(expense), 0) as total_expense')
            ->selectRaw('COALESCE(SUM(net), 0) as total_net')
            ->selectRaw('COALESCE(SUM(commission), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(bonus), 0) as total_bonus')
            ->selectRaw('COUNT(*) as entries_count')
            ->first();

        $items = $paginator->getCollection()->map(function (DailyEntry $entry) {
            return [
                'id' => $entry->id,
                'date' => $entry->date?->toDateString(),
                'employee' => $entry->employee ? [
                    'id' => $entry->employee->id,
                    'name' => $entry->employee->name,
                ] : null,
                'branch' => $entry->branch ? [
                    'id' => $entry->branch->id,
                    'name' => $entry->branch->name,
                ] : null,
                'sales' => (float) $entry->sales,
                'cash' => (float) $entry->cash,
                'expense' => (float) $entry->expense,
                'net' => (float) $entry->net,
                'commission' => (float) $entry->commission,
                'commission_rate' => $entry->commission_rate !== null ? (float) $entry->commission_rate : null,
                'bonus' => (float) $entry->bonus,
                'note' => $entry->note,
                'transactions_count' => (int) $entry->transactions_count,
                'is_locked' => (bool) $entry->is_locked,
                'source' => $entry->source,
                'created_at' => $entry->created_at?->toIso8601String(),
            ];
        })->values()->all();

        return $this->paginated($paginator, $items, [], [
            'summary' => [
                'total_sales' => (float) ($summary->total_sales ?? 0),
                'total_cash' => (float) ($summary->total_cash ?? 0),
                'total_expense' => (float) ($summary->total_expense ?? 0),
                'total_net' => (float) ($summary->total_net ?? 0),
                'total_commission' => (float) ($summary->total_commission ?? 0),
                'total_bonus' => (float) ($summary->total_bonus ?? 0),
                'entries_count' => (int) ($summary->entries_count ?? 0),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->requirePermission('Create:DailyEntry');

        $data = $request->validate([
            'employee_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where(fn($query) => $query->whereIn('role', User::employeeRoles())),
            ],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'date' => ['required', 'date'],
            'sales' => ['nullable', 'numeric', 'min:0'],
            'cash' => ['nullable', 'numeric', 'min:0'],
            'expense' => ['nullable', 'numeric', 'min:0'],
            'commission_rate' => ['nullable', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'bonus_reason' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'transactions_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $closed = DayClosure::query()
            ->where('branch_id', $data['branch_id'])
            ->whereDate('date', $data['date'])
            ->exists();

        if ($closed) {
            return $this->error('DAY_LOCKED', 'هذا اليوم مغلق ولا يمكن إضافة إدخالات جديدة', 409, [
                'date' => $data['date'],
            ]);
        }

        $existing = DailyEntry::query()
            ->where('employee_id', $data['employee_id'])
            ->whereDate('date', $data['date'])
            ->first();

        if ($existing) {
            return $this->error('DUPLICATE_ENTRY', 'يوجد إدخال مسجل لهذا الموظف في هذا التاريخ', 409, [
                'existing_entry_id' => $existing->id,
                'date' => $data['date'],
                'employee_id' => $data['employee_id'],
            ]);
        }

        $employee = User::query()->find($data['employee_id']);

        if ($employee && $employee->branch_id !== $data['branch_id']) {
            return $this->error('VALIDATION_ERROR', 'الموظف غير تابع لهذا الفرع', 422);
        }
        $commissionRate = $data['commission_rate'] ?? $employee?->commission_rate ?? 0;
        $sales = (float) ($data['sales'] ?? 0);
        $commission = round($sales * ((float) $commissionRate) / 100, 2);

        $entry = DailyEntry::create([
            'employee_id' => $data['employee_id'],
            'branch_id' => $data['branch_id'],
            'date' => $data['date'],
            'sales' => $sales,
            'cash' => (float) ($data['cash'] ?? 0),
            'expense' => (float) ($data['expense'] ?? 0),
            'commission_rate' => $commissionRate,
            'commission' => $commission,
            'bonus' => (float) ($data['bonus'] ?? 0),
            'bonus_reason' => $data['bonus_reason'] ?? null,
            'note' => $data['note'] ?? null,
            'transactions_count' => (int) ($data['transactions_count'] ?? 0),
            'source' => 'api',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $net = $entry->sales - $entry->cash - $entry->expense;
        $totalEarnings = $entry->commission + $entry->bonus;

        return $this->success([
            'id' => $entry->id,
            'date' => $entry->date?->toDateString(),
            'sales' => (float) $entry->sales,
            'cash' => (float) $entry->cash,
            'expense' => (float) $entry->expense,
            'net' => (float) $net,
            'commission' => (float) $entry->commission,
            'bonus' => (float) $entry->bonus,
            'total_earnings' => (float) $totalEarnings,
            'created_at' => $entry->created_at?->toIso8601String(),
        ], 'تم إنشاء الإدخال بنجاح', 201);
    }

    public function show(DailyEntry $dailyEntry)
    {
        $this->requirePermission('View:DailyEntry');

        $dailyEntry->load(['employee', 'branch', 'createdBy']);

        $totalEarnings = $dailyEntry->commission + $dailyEntry->bonus;

        return $this->success([
            'id' => $dailyEntry->id,
            'date' => $dailyEntry->date?->toDateString(),
            'employee' => $dailyEntry->employee ? [
                'id' => $dailyEntry->employee->id,
                'name' => $dailyEntry->employee->name,
                'phone' => $dailyEntry->employee->phone,
                'commission_rate' => (float) $dailyEntry->employee->commission_rate,
            ] : null,
            'branch' => $dailyEntry->branch ? [
                'id' => $dailyEntry->branch->id,
                'name' => $dailyEntry->branch->name,
                'code' => $dailyEntry->branch->code,
            ] : null,
            'sales' => (float) $dailyEntry->sales,
            'cash' => (float) $dailyEntry->cash,
            'expense' => (float) $dailyEntry->expense,
            'net' => (float) $dailyEntry->net,
            'commission' => (float) $dailyEntry->commission,
            'commission_rate' => $dailyEntry->commission_rate !== null ? (float) $dailyEntry->commission_rate : null,
            'bonus' => (float) $dailyEntry->bonus,
            'bonus_reason' => $dailyEntry->bonus_reason,
            'total_earnings' => (float) $totalEarnings,
            'note' => $dailyEntry->note,
            'transactions_count' => (int) $dailyEntry->transactions_count,
            'is_locked' => (bool) $dailyEntry->is_locked,
            'source' => $dailyEntry->source,
            'created_by' => $dailyEntry->createdBy ? [
                'id' => $dailyEntry->createdBy->id,
                'name' => $dailyEntry->createdBy->name,
            ] : null,
            'created_at' => $dailyEntry->created_at?->toIso8601String(),
            'updated_at' => $dailyEntry->updated_at?->toIso8601String(),
        ]);
    }

    public function update(Request $request, DailyEntry $dailyEntry)
    {
        $this->requirePermission('Update:DailyEntry');

        if ($dailyEntry->is_locked) {
            return $this->error('DAY_LOCKED', 'هذا اليوم مغلق ولا يمكن تعديل الإدخال', 409, [
                'date' => $dailyEntry->date?->toDateString(),
                'locked_at' => $dailyEntry->locked_at?->toIso8601String(),
                'locked_by' => $dailyEntry->locked_by,
            ]);
        }

        $closed = DayClosure::query()
            ->where('branch_id', $dailyEntry->branch_id)
            ->whereDate('date', $dailyEntry->date)
            ->exists();

        if ($closed) {
            return $this->error('DAY_LOCKED', 'هذا اليوم مغلق ولا يمكن تعديل الإدخال', 409, [
                'date' => $dailyEntry->date?->toDateString(),
            ]);
        }

        $data = $request->validate([
            'sales' => ['nullable', 'numeric', 'min:0'],
            'cash' => ['nullable', 'numeric', 'min:0'],
            'expense' => ['nullable', 'numeric', 'min:0'],
            'commission_rate' => ['nullable', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'bonus_reason' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'transactions_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $sales = array_key_exists('sales', $data) ? (float) $data['sales'] : (float) $dailyEntry->sales;
        $commissionRate = array_key_exists('commission_rate', $data)
            ? (float) $data['commission_rate']
            : (float) ($dailyEntry->commission_rate ?? 0);
        $commission = round($sales * $commissionRate / 100, 2);

        $dailyEntry->forceFill([
            'sales' => $sales,
            'cash' => array_key_exists('cash', $data) ? (float) $data['cash'] : $dailyEntry->cash,
            'expense' => array_key_exists('expense', $data) ? (float) $data['expense'] : $dailyEntry->expense,
            'commission_rate' => $commissionRate,
            'commission' => $commission,
            'bonus' => array_key_exists('bonus', $data) ? (float) $data['bonus'] : $dailyEntry->bonus,
            'bonus_reason' => $data['bonus_reason'] ?? $dailyEntry->bonus_reason,
            'note' => $data['note'] ?? $dailyEntry->note,
            'transactions_count' => array_key_exists('transactions_count', $data)
                ? (int) $data['transactions_count']
                : $dailyEntry->transactions_count,
            'updated_by' => $request->user()->id,
        ])->save();

        $net = $dailyEntry->sales - $dailyEntry->cash - $dailyEntry->expense;
        $totalEarnings = $dailyEntry->commission + $dailyEntry->bonus;

        return $this->success([
            'id' => $dailyEntry->id,
            'sales' => (float) $dailyEntry->sales,
            'cash' => (float) $dailyEntry->cash,
            'expense' => (float) $dailyEntry->expense,
            'net' => (float) $net,
            'commission' => (float) $dailyEntry->commission,
            'bonus' => (float) $dailyEntry->bonus,
            'total_earnings' => (float) $totalEarnings,
            'updated_at' => $dailyEntry->updated_at?->toIso8601String(),
        ], 'تم تحديث الإدخال بنجاح');
    }

    public function destroy(DailyEntry $dailyEntry)
    {
        $this->requirePermission('Delete:DailyEntry');

        if ($dailyEntry->is_locked) {
            return $this->error('DAY_LOCKED', 'هذا اليوم مغلق ولا يمكن حذف الإدخال', 409, [
                'date' => $dailyEntry->date?->toDateString(),
            ]);
        }

        $closed = DayClosure::query()
            ->where('branch_id', $dailyEntry->branch_id)
            ->whereDate('date', $dailyEntry->date)
            ->exists();

        if ($closed) {
            return $this->error('DAY_LOCKED', 'هذا اليوم مغلق ولا يمكن حذف الإدخال', 409, [
                'date' => $dailyEntry->date?->toDateString(),
            ]);
        }

        $dailyEntry->delete();

        return $this->success(null, 'تم حذف الإدخال بنجاح');
    }

    public function employeeStats(Request $request, User $employee)
    {
        $this->requirePermission('ViewAny:DailyEntry');

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = $data['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $data['date_to'] ?? now()->toDateString();

        $entries = DailyEntry::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->get();

        $totals = [
            'sales' => (float) $entries->sum('sales'),
            'cash' => (float) $entries->sum('cash'),
            'expense' => (float) $entries->sum('expense'),
            'net' => (float) $entries->sum('net'),
            'commission' => (float) $entries->sum('commission'),
            'bonus' => (float) $entries->sum('bonus'),
        ];

        $entriesCount = $entries->count();
        $totalEarnings = $totals['commission'] + $totals['bonus'];
        $periodDays = (int) (Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1);
        $workingDays = $entriesCount;
        $zeroDays = max($periodDays - $workingDays, 0);

        $averages = [
            'daily_sales' => $workingDays ? $totals['sales'] / $workingDays : 0.0,
            'daily_commission' => $workingDays ? $totals['commission'] / $workingDays : 0.0,
            'daily_bonus' => $workingDays ? $totals['bonus'] / $workingDays : 0.0,
        ];

        $bestDay = null;
        $worstDay = null;

        if ($entriesCount > 0) {
            $best = $entries->sortByDesc('sales')->first();
            $worst = $entries->sortBy('sales')->first();

            $bestDay = [
                'date' => $best->date?->toDateString(),
                'sales' => (float) $best->sales,
                'net' => (float) $best->net,
                'commission' => (float) $best->commission,
            ];

            $worstDay = [
                'date' => $worst->date?->toDateString(),
                'sales' => (float) $worst->sales,
                'net' => (float) $worst->net,
                'commission' => (float) $worst->commission,
            ];
        }

        return $this->success([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
            ],
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
                'days' => $periodDays,
            ],
            'totals' => array_merge($totals, [
                'total_earnings' => (float) $totalEarnings,
                'entries' => $entriesCount,
            ]),
            'averages' => $averages,
            'best_day' => $bestDay,
            'worst_day' => $worstDay,
            'working_days' => $workingDays,
            'zero_days' => $zeroDays,
        ]);
    }
}
