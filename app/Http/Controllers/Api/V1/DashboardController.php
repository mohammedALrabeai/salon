<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AdvanceRequest;
use App\Models\Branch;
use App\Models\DailyEntry;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class DashboardController extends ApiController
{
    public function overview(Request $request)
    {
        $this->requirePermission('ViewAny:DailyEntry');

        App::setLocale('ar');

        $today = now()->startOfDay();
        $yesterday = $today->copy()->subDay();
        $monthStart = $today->copy()->startOfMonth();

        $todayStats = $this->summarizeRange($today, $today);
        $yesterdayStats = $this->summarizeRange($yesterday, $yesterday);
        $monthStats = $this->summarizeRange($monthStart, $today);

        $totalBranches = Branch::query()->count();
        $totalEmployees = User::query()
            ->whereIn('role', User::employeeRoles())
            ->count();

        $pendingAdvances = AdvanceRequest::query()
            ->where('status', 'pending')
            ->count();

        $expiringDocuments = Document::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', $today->toDateString())
            ->whereDate('expiry_date', '<=', $today->copy()->addDays(60)->toDateString())
            ->count();

        return $this->success([
            'stats' => [
                'today' => [
                    'sales' => $todayStats['sales'],
                    'net' => $todayStats['net'],
                    'entries' => $todayStats['entries'],
                    'active_employees' => $todayStats['active_employees'],
                ],
                'yesterday' => [
                    'sales' => $yesterdayStats['sales'],
                    'net' => $yesterdayStats['net'],
                    'entries' => $yesterdayStats['entries'],
                ],
                'month' => [
                    'sales' => $monthStats['sales'],
                    'net' => $monthStats['net'],
                    'entries' => $monthStats['entries'],
                ],
                'total_branches' => $totalBranches,
                'total_employees' => $totalEmployees,
                'pending_advances' => $pendingAdvances,
                'expiring_documents' => $expiringDocuments,
                'growth' => [
                    'sales' => $this->growthPercentage($yesterdayStats['sales'], $todayStats['sales']),
                    'net' => $this->growthPercentage($yesterdayStats['net'], $todayStats['net']),
                    'entries' => $this->growthPercentage((float) $yesterdayStats['entries'], (float) $todayStats['entries']),
                ],
            ],
            'recent_entries' => $this->recentEntries(),
            'top_employees' => $this->topEmployees($monthStart, $today),
            'sales_chart' => $this->salesChart($today),
            'branches_distribution' => $this->branchesDistribution($monthStart, $today),
        ]);
    }

    private function summarizeRange(Carbon $from, Carbon $to): array
    {
        $totals = DailyEntry::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(net), 0) as total_net')
            ->selectRaw('COUNT(*) as entries_count')
            ->selectRaw('COUNT(DISTINCT user_id) as employees_count')
            ->first();

        return [
            'sales' => (float) ($totals->total_sales ?? 0),
            'net' => (float) ($totals->total_net ?? 0),
            'entries' => (int) ($totals->entries_count ?? 0),
            'active_employees' => (int) ($totals->employees_count ?? 0),
        ];
    }

    private function recentEntries(): array
    {
        return DailyEntry::query()
            ->with(['user', 'branch'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (DailyEntry $entry) {
                return [
                    'id' => $entry->id,
                    'employee_name' => $entry->user?->name ?? 'موظف',
                    'branch_name' => $entry->branch?->name ?? 'بدون فرع',
                    'amount' => (float) $entry->sales,
                    'commission' => (float) $entry->commission,
                    'time' => $entry->created_at
                        ? $entry->created_at->locale('ar')->diffForHumans()
                        : '',
                ];
            })
            ->values()
            ->all();
    }

    private function topEmployees(Carbon $from, Carbon $to): array
    {
        $rows = DailyEntry::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->select('user_id')
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(commission), 0) as total_commission')
            ->selectRaw('COUNT(*) as entries_count')
            ->groupBy('user_id')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        $users = User::query()
            ->whereIn('id', $rows->pluck('user_id')->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $user = $users->get($row->user_id);

            return [
                'id' => $row->user_id,
                'name' => $user?->name ?? 'موظف',
                'avatar' => $user?->avatar_url,
                'sales' => (float) $row->total_sales,
                'entries' => (int) $row->entries_count,
                'commission' => (float) $row->total_commission,
            ];
        })->values()->all();
    }

    private function salesChart(Carbon $today): array
    {
        $start = $today->copy()->subDays(6);
        $driver = DB::getDriverName();
        $dateExpression = match ($driver) {
            'pgsql' => "to_char(date, 'YYYY-MM-DD')",
            'sqlite' => "strftime('%Y-%m-%d', date)",
            default => "DATE_FORMAT(date, '%Y-%m-%d')",
        };

        $rows = DailyEntry::query()
            ->whereBetween('date', [$start->toDateString(), $today->toDateString()])
            ->selectRaw("{$dateExpression} as bucket")
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        return collect(range(0, 6))
            ->map(function (int $offset) use ($start, $rows) {
                $date = $start->copy()->addDays($offset);
                $key = $date->toDateString();
                $dayNames = [
                    'الأحد',
                    'الإثنين',
                    'الثلاثاء',
                    'الأربعاء',
                    'الخميس',
                    'الجمعة',
                    'السبت',
                ];

                return [
                    'name' => $dayNames[$date->dayOfWeek],
                    'value' => (float) ($rows->get($key)->total_sales ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function branchesDistribution(Carbon $from, Carbon $to): array
    {
        $palette = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#14b8a6'];

        $rows = DailyEntry::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->select('branch_id')
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->groupBy('branch_id')
            ->orderByDesc('total_sales')
            ->get();

        $branches = Branch::query()
            ->whereIn('id', $rows->pluck('branch_id')->all())
            ->get()
            ->keyBy('id');

        $totalSales = (float) $rows->sum('total_sales');

        if ($rows->isEmpty()) {
            return Branch::query()
                ->limit(5)
                ->get()
                ->values()
                ->map(function (Branch $branch, int $index) use ($palette) {
                    return [
                        'name' => $branch->name,
                        'value' => 0,
                        'color' => $palette[$index % count($palette)],
                    ];
                })
                ->all();
        }

        return $rows->values()->map(function ($row, int $index) use ($branches, $totalSales, $palette) {
            $branch = $branches->get($row->branch_id);
            $percentage = $totalSales > 0
                ? round(((float) $row->total_sales / $totalSales) * 100, 2)
                : 0;

            return [
                'name' => $branch?->name ?? 'فرع',
                'value' => $percentage,
                'color' => $palette[$index % count($palette)],
            ];
        })->all();
    }

    private function growthPercentage(float $previous, float $current): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
