<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\DailyEntry;
use App\Models\Employee;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ReportController extends ApiController
{
    public function sales(Request $request)
    {
        $this->requirePermission('ViewAny:DailyEntry');

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', 'uuid'],
            'employee_id' => ['nullable', 'uuid'],
            'group_by' => ['nullable', 'string'],
        ]);

        [$from, $to] = $this->resolveDateRange($data['date_from'] ?? null, $data['date_to'] ?? null);

        $query = DailyEntry::query()->whereBetween('date', [$from, $to]);

        if (! empty($data['branch_id'])) {
            $query->where('branch_id', $data['branch_id']);
        }

        if (! empty($data['employee_id'])) {
            $query->where('employee_id', $data['employee_id']);
        }

        $summary = (clone $query)
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(cash), 0) as total_cash')
            ->selectRaw('COALESCE(SUM(expense), 0) as total_expense')
            ->selectRaw('COALESCE(SUM(net), 0) as total_net')
            ->selectRaw('COALESCE(SUM(commission), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(bonus), 0) as total_bonus')
            ->selectRaw('COUNT(*) as entries_count')
            ->first();

        $periodDays = $this->countDays($from, $to);
        $avgDailySales = $periodDays > 0 ? (float) $summary->total_sales / $periodDays : 0.0;

        $groupBy = $data['group_by'] ?? 'day';
        $chartData = $this->salesChartData($query, $groupBy);

        $topEmployees = (clone $query)
            ->select('employee_id', DB::raw('COALESCE(SUM(sales), 0) as total_sales'), DB::raw('COALESCE(SUM(commission), 0) as total_commission'), DB::raw('COUNT(*) as entries_count'))
            ->groupBy('employee_id')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $employee = Employee::query()->find($row->employee_id);

                return [
                    'employee_id' => $row->employee_id,
                    'name' => $employee?->name,
                    'sales' => (float) $row->total_sales,
                    'commission' => (float) $row->total_commission,
                    'entries' => (int) $row->entries_count,
                ];
            })
            ->values()
            ->all();

        $branchesBreakdown = [];
        $totalSales = (float) ($summary->total_sales ?? 0);

        if (empty($data['branch_id'])) {
            $branchRows = (clone $query)
                ->select('branch_id', DB::raw('COALESCE(SUM(sales), 0) as total_sales'))
                ->groupBy('branch_id')
                ->get();

            $branchIds = $branchRows->pluck('branch_id')->all();
            $branches = Branch::query()->whereIn('id', $branchIds)->get()->keyBy('id');

            $branchesBreakdown = $branchRows->map(function ($row) use ($branches, $totalSales) {
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

        return $this->success([
            'period' => [
                'from' => $from,
                'to' => $to,
                'days' => $periodDays,
            ],
            'summary' => [
                'total_sales' => (float) ($summary->total_sales ?? 0),
                'total_cash' => (float) ($summary->total_cash ?? 0),
                'total_expense' => (float) ($summary->total_expense ?? 0),
                'total_net' => (float) ($summary->total_net ?? 0),
                'total_commission' => (float) ($summary->total_commission ?? 0),
                'total_bonus' => (float) ($summary->total_bonus ?? 0),
                'entries_count' => (int) ($summary->entries_count ?? 0),
                'avg_daily_sales' => $avgDailySales,
            ],
            'chart_data' => $chartData,
            'top_employees' => $topEmployees,
            'branches_breakdown' => $branchesBreakdown,
        ]);
    }

    public function employees(Request $request)
    {
        $this->requirePermission('ViewAny:Employee');

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => ['nullable', 'uuid'],
            'sort_by' => ['nullable', 'string'],
        ]);

        [$from, $to] = $this->resolveDateRange($data['date_from'] ?? null, $data['date_to'] ?? null);

        $query = DailyEntry::query()->whereBetween('date', [$from, $to]);

        if (! empty($data['branch_id'])) {
            $query->where('branch_id', $data['branch_id']);
        }

        $rows = $query
            ->select('employee_id', DB::raw('COALESCE(SUM(sales), 0) as total_sales'), DB::raw('COALESCE(SUM(commission), 0) as total_commission'), DB::raw('COALESCE(SUM(bonus), 0) as total_bonus'), DB::raw('COUNT(*) as entries_count'))
            ->groupBy('employee_id')
            ->get();

        $employeeIds = $rows->pluck('employee_id')->all();
        $employees = Employee::query()->whereIn('id', $employeeIds)->get()->keyBy('id');

        $bestDays = $this->bestDaysByEmployee($from, $to, $employeeIds);

        $items = $rows->map(function ($row) use ($employees, $bestDays) {
            $employee = $employees->get($row->employee_id);
            $totalEarnings = (float) $row->total_commission + (float) $row->total_bonus;
            $workingDays = (int) $row->entries_count;
            $avgDailySales = $workingDays > 0 ? (float) $row->total_sales / $workingDays : 0.0;
            $bestDay = $bestDays[$row->employee_id] ?? null;

            return [
                'employee' => [
                    'id' => $row->employee_id,
                    'name' => $employee?->name,
                    'role' => $employee?->role,
                ],
                'stats' => [
                    'total_sales' => (float) $row->total_sales,
                    'total_commission' => (float) $row->total_commission,
                    'total_bonus' => (float) $row->total_bonus,
                    'total_earnings' => $totalEarnings,
                    'entries' => (int) $row->entries_count,
                    'working_days' => $workingDays,
                    'avg_daily_sales' => $avgDailySales,
                    'best_day' => $bestDay,
                ],
            ];
        })->values()->all();

        $sortBy = $data['sort_by'] ?? 'sales';
        $items = collect($items)->sortByDesc(function ($item) use ($sortBy) {
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
        $this->requirePermission('ViewAny:Branch');

        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        [$from, $to] = $this->resolveDateRange($data['date_from'] ?? null, $data['date_to'] ?? null);

        $rows = DailyEntry::query()
            ->whereBetween('date', [$from, $to])
            ->select('branch_id', DB::raw('COALESCE(SUM(sales), 0) as total_sales'), DB::raw('COALESCE(SUM(net), 0) as total_net'), DB::raw('COUNT(*) as entries_count'), DB::raw('COUNT(DISTINCT employee_id) as employees_count'))
            ->groupBy('branch_id')
            ->get();

        $branchIds = $rows->pluck('branch_id')->all();
        $branches = Branch::query()->whereIn('id', $branchIds)->get()->keyBy('id');

        $maxSales = $rows->max('total_sales') ?: 0;

        $items = $rows->map(function ($row) use ($branches, $maxSales) {
            $branch = $branches->get($row->branch_id);
            $avgPerEmployee = $row->employees_count > 0 ? (float) $row->total_sales / (int) $row->employees_count : 0.0;
            $performance = $this->performanceLabel($maxSales > 0 ? (float) $row->total_sales / $maxSales : 0.0);

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
                'performance' => $performance,
            ];
        })->values()->sortByDesc(fn ($item) => $item['stats']['total_sales'])->values()->map(function ($item, $index) {
            $item['rank'] = $index + 1;

            return $item;
        })->all();

        return $this->success($items);
    }

    public function ledger(Request $request)
    {
        $this->requirePermission('ViewAny:LedgerEntry');

        $data = $request->validate([
            'party_type' => ['required', 'string', 'in:employee,branch,supplier,customer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        [$from, $to] = $this->resolveDateRange($data['date_from'] ?? null, $data['date_to'] ?? null);

        $query = LedgerEntry::query()
            ->where('party_type', $data['party_type'])
            ->whereBetween('date', [$from, $to]);

        $rows = $query->select('party_id', DB::raw("COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) as total_debit"), DB::raw("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) as total_credit"), DB::raw('COUNT(*) as entries_count'))
            ->groupBy('party_id')
            ->get();

        $partyIds = $rows->pluck('party_id')->all();
        $parties = [];

        if ($data['party_type'] === 'employee') {
            $parties = Employee::query()->whereIn('id', $partyIds)->get()->keyBy('id');
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
                    ? 'عليه '.number_format(abs($balance), 2).' ريال'
                    : ($balance > 0 ? 'له '.number_format($balance, 2).' ريال' : 'متوازن'),
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

    private function salesChartData($query, string $groupBy): array
    {
        $driver = DB::getDriverName();
        $periodExpression = $groupBy === 'month'
            ? ($driver === 'pgsql' ? "to_char(date, 'YYYY-MM')" : "DATE_FORMAT(date, '%Y-%m')")
            : ($driver === 'pgsql' ? "to_char(date, 'YYYY-MM-DD')" : "DATE_FORMAT(date, '%Y-%m-%d')");

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

    private function bestDaysByEmployee(string $from, string $to, array $employeeIds): array
    {
        if (empty($employeeIds)) {
            return [];
        }

        $rows = DailyEntry::query()
            ->whereBetween('date', [$from, $to])
            ->whereIn('employee_id', $employeeIds)
            ->select('employee_id', 'date', DB::raw('COALESCE(SUM(sales), 0) as total_sales'))
            ->groupBy('employee_id', 'date')
            ->get();

        $bestDays = [];

        foreach ($rows as $row) {
            $current = $bestDays[$row->employee_id] ?? null;
            if (! $current || $row->total_sales > $current['sales']) {
                $bestDays[$row->employee_id] = [
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
