<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\DailyEntry;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportController extends ApiController
{
    public function manager(Request $request)
    {
        $this->requireAdminOrPermission('ViewAny:DailyEntry');

        $data = $request->validate([
            'scope' => ['required', 'string', Rule::in(['overview', 'branch', 'employee'])],
            'period' => ['nullable', 'string', Rule::in(['today', 'month', 'custom'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'employee_id' => [
                'nullable',
                'uuid',
                Rule::exists('users', 'id')->where(fn($query) => $query->whereIn('role', User::employeeRoles())),
            ],
        ]);

        $scope = $data['scope'];
        $period = $data['period'] ?? 'today';

        if ($scope === 'branch' && empty($data['branch_id'])) {
            return $this->error('VALIDATION_ERROR', 'يرجى اختيار الفرع أولاً', 422);
        }

        if ($scope === 'employee' && empty($data['employee_id'])) {
            return $this->error('VALIDATION_ERROR', 'يرجى اختيار الموظف أولاً', 422);
        }

        $range = $this->resolveOverviewRange($period, $data['date_from'] ?? null, $data['date_to'] ?? null);
        $branchId = $scope === 'branch' ? ($data['branch_id'] ?? null) : null;
        $employeeId = $scope === 'employee' ? ($data['employee_id'] ?? null) : null;

        $query = $this->dailyEntriesQuery($range['from'], $range['to'], $branchId, $employeeId);
        $summary = $this->buildSummary($query);
        $previousSummary = $this->buildSummary(
            $this->dailyEntriesQuery(
                $range['previous_from'],
                $range['previous_to'],
                $branchId,
                $employeeId
            )
        );

        $employeesBreakdown = $scope === 'employee'
            ? []
            : $this->buildManagerEmployeesBreakdown($query, 12);

        $branchesBreakdown = $scope === 'overview'
            ? $this->buildManagerBranchesBreakdown($query)
            : [];

        $bestEntry = (clone $query)->orderByDesc('sales')->first();
        $worstEntry = (clone $query)->orderBy('sales')->first();

        return $this->success([
            'filters' => [
                'scope' => $scope,
                'period' => $period,
                'date_from' => $range['from'],
                'date_to' => $range['to'],
                'previous_from' => $range['previous_from'],
                'previous_to' => $range['previous_to'],
                'group_by' => $range['group_by'],
                'branch_id' => $branchId,
                'employee_id' => $employeeId,
            ],
            'scope' => $this->resolveManagerScope($scope, $branchId, $employeeId),
            'summary' => [
                ...$summary,
                'total_earnings' => round($summary['total_commission'] + $summary['total_bonus'], 2),
            ],
            'comparison' => $this->buildComparisonPayload($summary, $previousSummary),
            'chart_data' => $this->salesChartData($query, $range['group_by']),
            'highlights' => [
                'top_employee' => $scope === 'employee'
                    ? null
                    : collect($employeesBreakdown)->first(),
                'top_branch' => $scope === 'overview'
                    ? collect($branchesBreakdown)->first()
                    : null,
                'best_day' => $bestEntry ? $this->formatBestOrWorstEntry($bestEntry) : null,
                'worst_day' => $worstEntry ? $this->formatBestOrWorstEntry($worstEntry) : null,
            ],
            'branches_breakdown' => $branchesBreakdown,
            'employees_breakdown' => $employeesBreakdown,
            'entries' => $this->buildManagerEntries($query, 30),
        ]);
    }

    public function overview(Request $request)
    {
        $this->requireAdminOrPermission('ViewAny:DailyEntry');

        $data = $request->validate([
            'period' => ['nullable', 'string', Rule::in(['today', 'week', 'month', 'quarter', 'year', 'custom'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', 'uuid'],
            'employee_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $period = $data['period'] ?? 'month';
        $range = $this->resolveOverviewRange($period, $data['date_from'] ?? null, $data['date_to'] ?? null);
        $branchId = $data['branch_id'] ?? null;
        $employeeId = $data['employee_id'] ?? null;

        $overviewQuery = $this->dailyEntriesQuery($range['from'], $range['to'], $branchId);
        $overview = $this->buildSalesPayload($overviewQuery, $range['group_by'], empty($branchId), 10);

        return $this->success([
            'filters' => [
                'period' => $period,
                'date_from' => $range['from'],
                'date_to' => $range['to'],
                'previous_from' => $range['previous_from'],
                'previous_to' => $range['previous_to'],
                'group_by' => $range['group_by'],
                'employee_id' => $employeeId,
                'branch_id' => $branchId,
            ],
            'snapshots' => [
                'today' => $this->buildSnapshot(
                    now()->toDateString(),
                    now()->toDateString(),
                    $branchId
                ),
                'month' => $this->buildSnapshot(
                    now()->startOfMonth()->toDateString(),
                    now()->toDateString(),
                    $branchId
                ),
            ],
            'overview' => [
                'period' => [
                    'from' => $range['from'],
                    'to' => $range['to'],
                    'days' => $this->countDays($range['from'], $range['to']),
                ],
                ...$overview,
            ],
            'employee_report' => $employeeId
                ? $this->buildEmployeeReport($employeeId, $range['from'], $range['to'], $range['group_by'], $branchId)
                : null,
        ]);
    }

    public function sales(Request $request)
    {
        $this->requireAdminOrPermission('ViewAny:DailyEntry');

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', 'uuid'],
            'user_id' => ['nullable', 'uuid'],
            'group_by' => ['nullable', 'string', Rule::in(['day', 'month'])],
        ]);

        [$from, $to] = $this->resolveDateRange($data['date_from'] ?? null, $data['date_to'] ?? null);
        $query = $this->dailyEntriesQuery($from, $to, $data['branch_id'] ?? null, $data['user_id'] ?? null);
        $groupBy = $data['group_by'] ?? 'day';

        return $this->success([
            'period' => [
                'from' => $from,
                'to' => $to,
                'days' => $this->countDays($from, $to),
            ],
            ...$this->buildSalesPayload($query, $groupBy, empty($data['branch_id'])),
        ]);
    }

    public function users(Request $request)
    {
        $this->requireAdminOrAnyPermission(['ViewAny:Employee', 'ViewAny:User']);

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', 'uuid'],
            'sort_by' => ['nullable', 'string'],
        ]);

        [$from, $to] = $this->resolveDateRange($data['date_from'] ?? null, $data['date_to'] ?? null);
        $query = $this->dailyEntriesQuery($from, $to, $data['branch_id'] ?? null);

        $rows = (clone $query)
            ->select(
                'user_id',
                DB::raw('COALESCE(SUM(sales), 0) as total_sales'),
                DB::raw('COALESCE(SUM(commission), 0) as total_commission'),
                DB::raw('COALESCE(SUM(bonus), 0) as total_bonus'),
                DB::raw('COUNT(*) as entries_count')
            )
            ->groupBy('user_id')
            ->get();

        $userIds = $rows->pluck('user_id')->all();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');
        $bestDays = $this->bestDaysByEmployee($from, $to, $userIds);

        $items = $rows->map(function ($row) use ($users, $bestDays) {
            $user = $users->get($row->user_id);
            $totalEarnings = (float) $row->total_commission + (float) $row->total_bonus;
            $workingDays = (int) $row->entries_count;
            $avgDailySales = $workingDays > 0 ? (float) $row->total_sales / $workingDays : 0.0;

            return [
                'user' => [
                    'id' => $row->user_id,
                    'name' => $user?->name,
                    'role' => $user?->role,
                ],
                'stats' => [
                    'total_sales' => (float) $row->total_sales,
                    'total_commission' => (float) $row->total_commission,
                    'total_bonus' => (float) $row->total_bonus,
                    'total_earnings' => $totalEarnings,
                    'entries' => (int) $row->entries_count,
                    'working_days' => $workingDays,
                    'avg_daily_sales' => $avgDailySales,
                    'best_day' => $bestDays[$row->user_id] ?? null,
                ],
            ];
        })->values();

        $sortBy = $data['sort_by'] ?? 'sales';
        $items = $items->sortByDesc(function ($item) use ($sortBy) {
            return match ($sortBy) {
                'commission' => $item['stats']['total_commission'],
                'bonus' => $item['stats']['total_bonus'],
                default => $item['stats']['total_sales'],
            };
        })->values()->map(function ($item, $index) {
            $item['rank'] = $index + 1;

            return $item;
        })->all();

        return $this->success($items);
    }

    public function branches(Request $request)
    {
        $this->requireAdminOrPermission('ViewAny:Branch');

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        [$from, $to] = $this->resolveDateRange($data['date_from'] ?? null, $data['date_to'] ?? null);

        $rows = DailyEntry::query()
            ->whereBetween('date', [$from, $to])
            ->select(
                'branch_id',
                DB::raw('COALESCE(SUM(sales), 0) as total_sales'),
                DB::raw('COALESCE(SUM(net), 0) as total_net'),
                DB::raw('COUNT(*) as entries_count'),
                DB::raw('COUNT(DISTINCT user_id) as employees_count')
            )
            ->groupBy('branch_id')
            ->get();

        $branchIds = $rows->pluck('branch_id')->all();
        $branches = Branch::query()->whereIn('id', $branchIds)->get()->keyBy('id');
        $maxSales = $rows->max('total_sales') ?: 0;

        $items = $rows->map(function ($row) use ($branches, $maxSales) {
            $branch = $branches->get($row->branch_id);
            $avgPerEmployee = $row->employees_count > 0
                ? (float) $row->total_sales / (int) $row->employees_count
                : 0.0;

            return [
                'branch' => [
                    'id' => $row->branch_id,
                    'name' => $branch?->name,
                    'code' => $branch?->code,
                ],
                'stats' => [
                    'total_sales' => (float) $row->total_sales,
                    'total_net' => (float) $row->total_net,
                    'entries' => (int) $row->entries_count,
                    'employees_count' => (int) $row->employees_count,
                    'avg_per_employee' => $avgPerEmployee,
                ],
                'performance' => $this->performanceLabel(
                    $maxSales > 0 ? (float) $row->total_sales / $maxSales : 0.0
                ),
            ];
        })->values()->sortByDesc(fn($item) => $item['stats']['total_sales'])->values()->map(function ($item, $index) {
            $item['rank'] = $index + 1;

            return $item;
        })->all();

        return $this->success($items);
    }

    public function ledger(Request $request)
    {
        $this->requireAdminOrPermission('ViewAny:LedgerEntry');

        $data = $request->validate([
            'party_type' => ['required', 'string', 'in:employee,branch,supplier,customer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        [$from, $to] = $this->resolveDateRange($data['date_from'] ?? null, $data['date_to'] ?? null);

        $query = LedgerEntry::query()
            ->where('party_type', $data['party_type'])
            ->whereBetween('date', [$from, $to]);

        $rows = $query
            ->select(
                'party_id',
                DB::raw("COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) as total_debit"),
                DB::raw("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) as total_credit"),
                DB::raw('COUNT(*) as entries_count')
            )
            ->groupBy('party_id')
            ->get();

        $partyIds = $rows->pluck('party_id')->all();
        $parties = [];

        if ($data['party_type'] === 'user') {
            $parties = User::query()->whereIn('id', $partyIds)->get()->keyBy('id');
        } elseif ($data['party_type'] === 'branch') {
            $parties = Branch::query()->whereIn('id', $partyIds)->get()->keyBy('id');
        }

        $accounts = $rows->map(function ($row) use ($parties) {
            $party = $parties[$row->party_id] ?? null;
            $balance = (float) $row->total_credit - (float) $row->total_debit;

            return [
                'party' => [
                    'id' => $row->party_id,
                    'name' => $party?->name,
                ],
                'balance' => $balance,
                'balance_label' => $balance < 0
                    ? 'عليه ' . number_format(abs($balance), 2) . ' ريال'
                    : ($balance > 0 ? 'له ' . number_format($balance, 2) . ' ريال' : 'متوازن'),
                'total_debit' => (float) $row->total_debit,
                'total_credit' => (float) $row->total_credit,
                'entries_count' => (int) $row->entries_count,
            ];
        })->values()->all();

        $summary = $query
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) as total_debit")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) as total_credit")
            ->first();

        $netBalance = (float) ($summary->total_credit ?? 0) - (float) ($summary->total_debit ?? 0);

        return $this->success([
            'accounts' => $accounts,
            'summary' => [
                'total_debit' => (float) ($summary->total_debit ?? 0),
                'total_credit' => (float) ($summary->total_credit ?? 0),
                'net_balance' => $netBalance,
            ],
        ]);
    }

    private function buildSalesPayload(
        Builder $query,
        string $groupBy,
        bool $includeBranches = true,
        int $topEmployeesLimit = 5
    ): array {
        $summary = $this->buildSummary($query);
        $topEmployees = $this->buildTopEmployees($query, $topEmployeesLimit);
        $branchesBreakdown = $includeBranches
            ? $this->buildBranchesBreakdown($query, $summary['total_sales'])
            : [];

        return [
            'summary' => $summary,
            'chart_data' => $this->salesChartData($query, $groupBy),
            'top_employees' => $topEmployees,
            'branches_breakdown' => $branchesBreakdown,
        ];
    }

    private function buildSnapshot(string $from, string $to, ?string $branchId = null): array
    {
        $query = $this->dailyEntriesQuery($from, $to, $branchId);
        $summary = $this->buildSummary($query);
        $topEmployee = collect($this->buildTopEmployees($query, 1))->first();
        $topBranch = collect($this->buildBranchesBreakdown($query, $summary['total_sales']))->first();

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
                'days' => $this->countDays($from, $to),
            ],
            'summary' => $summary,
            'top_employee' => $topEmployee ?: null,
            'top_branch' => $topBranch ?: null,
        ];
    }

    private function resolveManagerScope(string $scope, ?string $branchId, ?string $employeeId): array
    {
        if ($scope === 'branch') {
            $branch = $branchId ? Branch::query()->find($branchId) : null;

            return [
                'type' => 'branch',
                'id' => $branch?->id,
                'name' => $branch?->name ?? 'فرع',
                'title' => $branch ? "تقرير {$branch->name}" : 'تقرير الفرع',
                'subtitle' => 'يعرض الأداء الكامل لهذا الفرع فقط خلال الفترة المحددة',
            ];
        }

        if ($scope === 'employee') {
            $employee = $employeeId
                ? User::query()->with('branch')->find($employeeId)
                : null;

            return [
                'type' => 'employee',
                'id' => $employee?->id,
                'name' => $employee?->name ?? 'موظف',
                'branch_name' => $employee?->branch?->name,
                'title' => $employee ? "تقرير {$employee->name}" : 'تقرير الموظف',
                'subtitle' => 'يعرض أداء الموظف المحدد فقط مع آخر العمليات المسجلة له',
            ];
        }

        return [
            'type' => 'overview',
            'id' => null,
            'name' => 'كل الفروع',
            'title' => 'التقرير العام',
            'subtitle' => 'يعرض ملخص جميع الفروع والموظفين خلال الفترة المحددة',
        ];
    }

    private function buildEmployeeReport(
        string $employeeId,
        string $from,
        string $to,
        string $groupBy,
        ?string $branchId = null
    ): ?array {
        $user = User::query()->find($employeeId);

        if (!$user) {
            return null;
        }

        $periodQuery = $this->dailyEntriesQuery($from, $to, $branchId, $employeeId);
        $periodSummary = $this->buildSummary($periodQuery);
        $todaySummary = $this->buildSummary(
            $this->dailyEntriesQuery(now()->toDateString(), now()->toDateString(), $branchId, $employeeId)
        );
        $monthSummary = $this->buildSummary(
            $this->dailyEntriesQuery(now()->startOfMonth()->toDateString(), now()->toDateString(), $branchId, $employeeId)
        );

        $entriesQuery = $this->dailyEntriesQuery($from, $to, $branchId, $employeeId)->with(['branch']);
        $entries = (clone $entriesQuery)
            ->orderByDesc('date')
            ->limit(100)
            ->get();

        $bestEntry = (clone $entriesQuery)->orderByDesc('sales')->first();
        $worstEntry = (clone $entriesQuery)->orderBy('sales')->first();
        $workingDays = (int) $periodSummary['entries_count'];
        $periodDays = $this->countDays($from, $to);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'branch_id' => $user->branch_id,
                'commission_rate' => $user->commission_rate !== null ? (float) $user->commission_rate : null,
            ],
            'period' => [
                'from' => $from,
                'to' => $to,
                'days' => $periodDays,
            ],
            'period_summary' => $this->formatEmployeeSummary($periodSummary),
            'today_summary' => $this->formatEmployeeSummary($todaySummary),
            'month_summary' => $this->formatEmployeeSummary($monthSummary),
            'averages' => [
                'daily_sales' => $workingDays > 0 ? round($periodSummary['total_sales'] / $workingDays, 2) : 0.0,
                'daily_commission' => $workingDays > 0 ? round($periodSummary['total_commission'] / $workingDays, 2) : 0.0,
                'daily_bonus' => $workingDays > 0 ? round($periodSummary['total_bonus'] / $workingDays, 2) : 0.0,
            ],
            'best_day' => $bestEntry ? $this->formatBestOrWorstEntry($bestEntry) : null,
            'worst_day' => $worstEntry ? $this->formatBestOrWorstEntry($worstEntry) : null,
            'working_days' => $workingDays,
            'zero_days' => max($periodDays - $workingDays, 0),
            'chart_data' => $this->salesChartData(
                $this->dailyEntriesQuery($from, $to, $branchId, $employeeId),
                $groupBy
            ),
            'entries' => $entries->map(fn(DailyEntry $entry) => $this->serializeEntry($entry))->values()->all(),
        ];
    }

    private function formatEmployeeSummary(array $summary): array
    {
        return [
            'sales' => $summary['total_sales'],
            'cash' => $summary['total_cash'],
            'expense' => $summary['total_expense'],
            'net' => $summary['total_net'],
            'commission' => $summary['total_commission'],
            'bonus' => $summary['total_bonus'],
            'total_earnings' => round($summary['total_commission'] + $summary['total_bonus'], 2),
            'entries' => $summary['entries_count'],
            'payment_type_breakdown' => $summary['payment_type_breakdown'],
        ];
    }

    private function formatBestOrWorstEntry(DailyEntry $entry): array
    {
        return [
            'date' => $entry->date?->toDateString(),
            'sales' => (float) $entry->sales,
            'net' => (float) $entry->net,
            'commission' => (float) $entry->commission,
        ];
    }

    private function serializeEntry(DailyEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'date' => $entry->date?->toDateString(),
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
            'payment_type' => $entry->payment_type ?? DailyEntry::PAYMENT_TYPE_CASH,
            'is_locked' => (bool) $entry->is_locked,
        ];
    }

    private function buildSummary(Builder $query): array
    {
        $summary = (clone $query)
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(cash), 0) as total_cash')
            ->selectRaw('COALESCE(SUM(expense), 0) as total_expense')
            ->selectRaw('COALESCE(SUM(net), 0) as total_net')
            ->selectRaw('COALESCE(SUM(commission), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(bonus), 0) as total_bonus')
            ->selectRaw('COUNT(*) as entries_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type = 'cash' THEN sales ELSE 0 END), 0) as total_cash_payments")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type = 'network' THEN sales ELSE 0 END), 0) as total_network_payments")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type = 'purchases' THEN sales ELSE 0 END), 0) as total_purchases_payments")
            ->first();

        return [
            'total_sales' => (float) ($summary->total_sales ?? 0),
            'total_cash' => (float) ($summary->total_cash ?? 0),
            'total_expense' => (float) ($summary->total_expense ?? 0),
            'total_net' => (float) ($summary->total_net ?? 0),
            'total_commission' => (float) ($summary->total_commission ?? 0),
            'total_bonus' => (float) ($summary->total_bonus ?? 0),
            'entries_count' => (int) ($summary->entries_count ?? 0),
            'avg_daily_sales' => 0.0,
            'payment_type_breakdown' => [
                'cash' => (float) ($summary->total_cash_payments ?? 0),
                'network' => (float) ($summary->total_network_payments ?? 0),
                'purchases' => (float) ($summary->total_purchases_payments ?? 0),
            ],
        ];
    }

    private function buildTopEmployees(Builder $query, int $limit = 5): array
    {
        $rows = (clone $query)
            ->select(
                'user_id',
                DB::raw('COALESCE(SUM(sales), 0) as total_sales'),
                DB::raw('COALESCE(SUM(commission), 0) as total_commission'),
                DB::raw('COUNT(*) as entries_count')
            )
            ->groupBy('user_id')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();

        $users = User::query()
            ->whereIn('id', $rows->pluck('user_id')->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $user = $users->get($row->user_id);

            return [
                'user_id' => $row->user_id,
                'name' => $user?->name,
                'sales' => (float) $row->total_sales,
                'commission' => (float) $row->total_commission,
                'entries' => (int) $row->entries_count,
            ];
        })->values()->all();
    }

    private function buildManagerEmployeesBreakdown(Builder $query, int $limit = 12): array
    {
        $rows = (clone $query)
            ->select(
                'user_id',
                DB::raw('COALESCE(SUM(sales), 0) as total_sales'),
                DB::raw('COALESCE(SUM(net), 0) as total_net'),
                DB::raw('COALESCE(SUM(commission), 0) as total_commission'),
                DB::raw('COALESCE(SUM(bonus), 0) as total_bonus'),
                DB::raw('COUNT(*) as entries_count')
            )
            ->groupBy('user_id')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();

        $users = User::query()
            ->with('branch')
            ->whereIn('id', $rows->pluck('user_id')->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $user = $users->get($row->user_id);
            $bonus = (float) $row->total_bonus;
            $commission = (float) $row->total_commission;

            return [
                'user_id' => $row->user_id,
                'name' => $user?->name,
                'branch_name' => $user?->branch?->name,
                'sales' => (float) $row->total_sales,
                'net' => (float) $row->total_net,
                'commission' => $commission,
                'bonus' => $bonus,
                'total_earnings' => round($commission + $bonus, 2),
                'entries' => (int) $row->entries_count,
            ];
        })->values()->all();
    }

    private function buildManagerBranchesBreakdown(Builder $query): array
    {
        $rows = (clone $query)
            ->select(
                'branch_id',
                DB::raw('COALESCE(SUM(sales), 0) as total_sales'),
                DB::raw('COALESCE(SUM(net), 0) as total_net'),
                DB::raw('COUNT(*) as entries_count'),
                DB::raw('COUNT(DISTINCT user_id) as employees_count')
            )
            ->groupBy('branch_id')
            ->orderByDesc('total_sales')
            ->get();

        $branches = Branch::query()
            ->whereIn('id', $rows->pluck('branch_id')->all())
            ->get()
            ->keyBy('id');

        $totalSales = (float) $rows->sum('total_sales');

        return $rows->map(function ($row) use ($branches, $totalSales) {
            $branch = $branches->get($row->branch_id);
            $sales = (float) $row->total_sales;

            return [
                'branch_id' => $row->branch_id,
                'name' => $branch?->name,
                'sales' => $sales,
                'net' => (float) $row->total_net,
                'entries' => (int) $row->entries_count,
                'employees_count' => (int) $row->employees_count,
                'percentage' => $totalSales > 0 ? round(($sales / $totalSales) * 100, 2) : 0.0,
            ];
        })->values()->all();
    }

    private function buildManagerEntries(Builder $query, int $limit = 30): array
    {
        return (clone $query)
            ->with(['user', 'branch'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn(DailyEntry $entry) => $this->serializeManagerEntry($entry))
            ->values()
            ->all();
    }

    private function serializeManagerEntry(DailyEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'date' => $entry->date?->toDateString(),
            'user' => $entry->user ? [
                'id' => $entry->user->id,
                'name' => $entry->user->name,
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
            'bonus' => (float) $entry->bonus,
            'payment_type' => $entry->payment_type ?? DailyEntry::PAYMENT_TYPE_CASH,
            'transactions_count' => (int) $entry->transactions_count,
            'note' => $entry->note,
            'is_locked' => (bool) $entry->is_locked,
        ];
    }

    private function buildComparisonPayload(array $summary, array $previousSummary): array
    {
        $currentEarnings = (float) $summary['total_commission'] + (float) $summary['total_bonus'];
        $previousEarnings = (float) $previousSummary['total_commission'] + (float) $previousSummary['total_bonus'];

        return [
            'previous_summary' => [
                'total_sales' => (float) $previousSummary['total_sales'],
                'total_net' => (float) $previousSummary['total_net'],
                'entries_count' => (int) $previousSummary['entries_count'],
                'total_earnings' => round($previousEarnings, 2),
            ],
            'sales_change' => $this->percentageChange(
                (float) $summary['total_sales'],
                (float) $previousSummary['total_sales']
            ),
            'net_change' => $this->percentageChange(
                (float) $summary['total_net'],
                (float) $previousSummary['total_net']
            ),
            'entries_change' => $this->percentageChange(
                (float) $summary['entries_count'],
                (float) $previousSummary['entries_count']
            ),
            'earnings_change' => $this->percentageChange(
                $currentEarnings,
                $previousEarnings
            ),
        ];
    }

    private function percentageChange(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function buildBranchesBreakdown(Builder $query, float $totalSales): array
    {
        $rows = (clone $query)
            ->select('branch_id', DB::raw('COALESCE(SUM(sales), 0) as total_sales'))
            ->groupBy('branch_id')
            ->get();

        $branches = Branch::query()
            ->whereIn('id', $rows->pluck('branch_id')->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($branches, $totalSales) {
            $branch = $branches->get($row->branch_id);
            $sales = (float) $row->total_sales;
            $percentage = $totalSales > 0 ? ($sales / $totalSales) * 100 : 0.0;

            return [
                'branch_id' => $row->branch_id,
                'name' => $branch?->name,
                'sales' => $sales,
                'percentage' => round($percentage, 2),
            ];
        })->values()->all();
    }

    private function dailyEntriesQuery(
        string $from,
        string $to,
        ?string $branchId = null,
        ?string $userId = null
    ): Builder {
        $query = DailyEntry::query()->whereBetween('date', [$from, $to]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    private function resolveOverviewRange(string $period, ?string $from, ?string $to): array
    {
        $today = now()->toDateString();

        return match ($period) {
            'today' => [
                'from' => $today,
                'to' => $today,
                'previous_from' => now()->subDay()->toDateString(),
                'previous_to' => now()->subDay()->toDateString(),
                'group_by' => 'day',
            ],
            'week' => [
                'from' => now()->subDays(6)->toDateString(),
                'to' => $today,
                'previous_from' => now()->subDays(13)->toDateString(),
                'previous_to' => now()->subDays(7)->toDateString(),
                'group_by' => 'day',
            ],
            'month' => [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => $today,
                'previous_from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'previous_to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
                'group_by' => 'day',
            ],
            'quarter' => [
                'from' => now()->firstOfQuarter()->toDateString(),
                'to' => $today,
                'previous_from' => now()->subQuarter()->firstOfQuarter()->toDateString(),
                'previous_to' => now()->subQuarter()->lastOfQuarter()->toDateString(),
                'group_by' => 'month',
            ],
            'year' => [
                'from' => now()->startOfYear()->toDateString(),
                'to' => $today,
                'previous_from' => now()->subYear()->startOfYear()->toDateString(),
                'previous_to' => now()->subYear()->endOfYear()->toDateString(),
                'group_by' => 'month',
            ],
            default => $this->resolveCustomOverviewRange($from, $to),
        };
    }

    private function resolveCustomOverviewRange(?string $from, ?string $to): array
    {
        [$resolvedFrom, $resolvedTo] = $this->resolveDateRange($from, $to);
        $days = $this->countDays($resolvedFrom, $resolvedTo);
        $previousTo = Carbon::parse($resolvedFrom)->subDay();
        $previousFrom = (clone $previousTo)->subDays(max($days - 1, 0));

        return [
            'from' => $resolvedFrom,
            'to' => $resolvedTo,
            'previous_from' => $previousFrom->toDateString(),
            'previous_to' => $previousTo->toDateString(),
            'group_by' => $days > 45 ? 'month' : 'day',
        ];
    }

    private function resolveDateRange(?string $from, ?string $to): array
    {
        $start = $from ?: now()->startOfMonth()->toDateString();
        $end = $to ?: now()->toDateString();

        return [$start, $end];
    }

    private function countDays(string $from, string $to): int
    {
        return (int) (Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1);
    }

    private function salesChartData(Builder $query, string $groupBy): array
    {
        $driver = DB::getDriverName();
        $periodExpression = $groupBy === 'month'
            ? match ($driver) {
                'pgsql' => "to_char(date, 'YYYY-MM')",
                'sqlite' => "strftime('%Y-%m', date)",
                default => "DATE_FORMAT(date, '%Y-%m')",
            }
            : match ($driver) {
                'pgsql' => "to_char(date, 'YYYY-MM-DD')",
                'sqlite' => "strftime('%Y-%m-%d', date)",
                default => "DATE_FORMAT(date, '%Y-%m-%d')",
            };

        $rows = (clone $query)
            ->selectRaw("{$periodExpression} as period")
            ->selectRaw('COALESCE(SUM(sales), 0) as sales')
            ->selectRaw('COALESCE(SUM(net), 0) as net')
            ->selectRaw('COUNT(*) as entries')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $rows->map(function ($row) use ($groupBy) {
            return $groupBy === 'month'
                ? [
                    'month' => $row->period,
                    'sales' => (float) $row->sales,
                    'net' => (float) $row->net,
                    'entries' => (int) $row->entries,
                ]
                : [
                    'date' => $row->period,
                    'sales' => (float) $row->sales,
                    'net' => (float) $row->net,
                    'entries' => (int) $row->entries,
                ];
        })->values()->all();
    }

    private function bestDaysByEmployee(string $from, string $to, array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $rows = DailyEntry::query()
            ->whereBetween('date', [$from, $to])
            ->whereIn('user_id', $userIds)
            ->select('user_id', 'date', DB::raw('COALESCE(SUM(sales), 0) as total_sales'))
            ->groupBy('user_id', 'date')
            ->get();

        $bestDays = [];

        foreach ($rows as $row) {
            $current = $bestDays[$row->user_id] ?? null;

            if (!$current || $row->total_sales > $current['sales']) {
                $bestDays[$row->user_id] = [
                    'date' => $row->date?->toDateString(),
                    'sales' => (float) $row->total_sales,
                ];
            }
        }

        return $bestDays;
    }

    private function performanceLabel(float $ratio): string
    {
        if ($ratio >= 0.8) {
            return 'excellent';
        }

        if ($ratio >= 0.6) {
            return 'good';
        }

        if ($ratio >= 0.4) {
            return 'average';
        }

        return 'low';
    }
}
