<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\DailyEntry;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends ApiController
{
    public function dashboard(Request $request)
    {
        $this->requirePermission('ViewAny:DailyEntry');

        $data = $request->validate([
            'period' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'uuid'],
        ]);

        $period = $data['period'] ?? 'today';
        [$from, $to, $prevFrom, $prevTo] = $this->resolvePeriod($period);

        $query = DailyEntry::query()->whereBetween('date', [$from, $to]);
        $prevQuery = DailyEntry::query()->whereBetween('date', [$prevFrom, $prevTo]);

        if (! empty($data['branch_id'])) {
            $query->where('branch_id', $data['branch_id']);
            $prevQuery->where('branch_id', $data['branch_id']);
        }

        $current = $this->aggregateKpis($query);
        $previous = $this->aggregateKpis($prevQuery);

        $kpis = [
            'sales' => $this->kpiBlock($current['sales'], $previous['sales'], 'مقارنة بالفترة السابقة'),
            'net' => $this->kpiBlock($current['net'], $previous['net'], null),
            'entries' => $this->kpiBlock($current['entries'], $previous['entries'], null),
            'active_employees' => $this->kpiBlock($current['active_employees'], $previous['active_employees'], null),
        ];

        $chart = [
            'sales_trend' => $this->salesTrend($query, $period),
        ];

        $topPerformers = (clone $query)
            ->select('employee_id', DB::raw('COALESCE(SUM(sales), 0) as total_sales'))
            ->groupBy('employee_id')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(function ($row, $index) {
                $employee = Employee::query()->find($row->employee_id);

                return [
                    'employee' => $employee?->name,
                    'sales' => (float) $row->total_sales,
                    'rank' => $index + 1,
                ];
            })
            ->values()
            ->all();

        return $this->success([
            'period' => $period,
            'date' => $to,
            'kpis' => $kpis,
            'chart' => $chart,
            'top_performers' => $topPerformers,
        ]);
    }

    public function compare(Request $request)
    {
        $this->requirePermission('ViewAny:DailyEntry');

        $data = $request->validate([
            'period1_from' => ['required', 'date'],
            'period1_to' => ['required', 'date', 'after_or_equal:period1_from'],
            'period2_from' => ['required', 'date'],
            'period2_to' => ['required', 'date', 'after_or_equal:period2_from'],
        ]);

        $period1 = $this->aggregateKpis(DailyEntry::query()->whereBetween('date', [$data['period1_from'], $data['period1_to']]));
        $period2 = $this->aggregateKpis(DailyEntry::query()->whereBetween('date', [$data['period2_from'], $data['period2_to']]));

        $comparison = [
            'sales_change' => $this->percentageChange($period1['sales'], $period2['sales']),
            'net_change' => $this->percentageChange($period1['net'], $period2['net']),
            'entries_change' => $this->percentageChange($period1['entries'], $period2['entries']),
        ];

        $trend = 'stable';
        if ($comparison['sales_change'] > 0) {
            $trend = 'up';
        } elseif ($comparison['sales_change'] < 0) {
            $trend = 'down';
        }

        return $this->success([
            'period1' => [
                'label' => $data['period1_from'].' إلى '.$data['period1_to'],
                'sales' => $period1['sales'],
                'net' => $period1['net'],
                'entries' => $period1['entries'],
            ],
            'period2' => [
                'label' => $data['period2_from'].' إلى '.$data['period2_to'],
                'sales' => $period2['sales'],
                'net' => $period2['net'],
                'entries' => $period2['entries'],
            ],
            'comparison' => array_merge($comparison, [
                'trend' => $trend,
            ]),
        ]);
    }

    private function resolvePeriod(string $period): array
    {
        $today = now()->startOfDay();

        return match ($period) {
            'week' => [
                $today->copy()->subDays(6)->toDateString(),
                $today->toDateString(),
                $today->copy()->subDays(13)->toDateString(),
                $today->copy()->subDays(7)->toDateString(),
            ],
            'month' => [
                $today->copy()->startOfMonth()->toDateString(),
                $today->toDateString(),
                $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'year' => [
                $today->copy()->startOfYear()->toDateString(),
                $today->toDateString(),
                $today->copy()->subYear()->startOfYear()->toDateString(),
                $today->copy()->subYear()->endOfYear()->toDateString(),
            ],
            default => [
                $today->toDateString(),
                $today->toDateString(),
                $today->copy()->subDay()->toDateString(),
                $today->copy()->subDay()->toDateString(),
            ],
        };
    }

    private function aggregateKpis($query): array
    {
        $totals = (clone $query)
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(net), 0) as total_net')
            ->selectRaw('COUNT(*) as entries_count')
            ->selectRaw('COUNT(DISTINCT employee_id) as employees_count')
            ->first();

        return [
            'sales' => (float) ($totals->total_sales ?? 0),
            'net' => (float) ($totals->total_net ?? 0),
            'entries' => (int) ($totals->entries_count ?? 0),
            'active_employees' => (int) ($totals->employees_count ?? 0),
        ];
    }

    private function kpiBlock(float $current, float $previous, ?string $comparisonText): array
    {
        $change = $this->percentageChange($previous, $current);

        $trend = 'stable';
        if ($change > 0.1) {
            $trend = 'up';
        } elseif ($change < -0.1) {
            $trend = 'down';
        }

        $payload = [
            'value' => $current,
            'change' => $change,
            'trend' => $trend,
        ];

        if ($comparisonText) {
            $payload['comparison'] = $comparisonText;
        }

        return $payload;
    }

    private function percentageChange(float $previous, float $current): float
    {
        if ($previous == 0.0) {
            return 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function salesTrend($query, string $period): array
    {
        $driver = DB::getDriverName();

        if ($period === 'today') {
            $hourExpression = $driver === 'pgsql'
                ? "to_char(created_at, 'HH24:00')"
                : "DATE_FORMAT(created_at, '%H:00')";

            $rows = (clone $query)
                ->selectRaw("{$hourExpression} as bucket")
                ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get();

            return $rows->map(function ($row) {
                return [
                    'hour' => $row->bucket,
                    'sales' => (float) $row->total_sales,
                ];
            })->values()->all();
        }

        $dateExpression = $driver === 'pgsql'
            ? "to_char(date, 'YYYY-MM-DD')"
            : "DATE_FORMAT(date, '%Y-%m-%d')";

        $rows = (clone $query)
            ->selectRaw("{$dateExpression} as bucket")
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return $rows->map(function ($row) {
            return [
                'date' => $row->bucket,
                'sales' => (float) $row->total_sales,
            ];
        })->values()->all();
    }
}
