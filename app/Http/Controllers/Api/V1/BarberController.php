<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AdvanceRequest;
use App\Models\DailyEntry;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BarberController extends ApiController
{
    /**
     * GET /barber/dashboard
     * Returns everything the barber dashboard needs in ONE call.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        // 1. Today's stats
        $todayStats = DailyEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->selectRaw('COALESCE(SUM(sales), 0) as sales')
            ->selectRaw('COALESCE(SUM(commission), 0) as commission')
            ->selectRaw('COALESCE(SUM(bonus), 0) as bonus')
            ->selectRaw('COALESCE(SUM(net), 0) as net')
            ->selectRaw('COUNT(*) as entries')
            ->first();

        // 2. This month's stats
        $monthStats = DailyEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$monthStart, $today])
            ->selectRaw('COALESCE(SUM(sales), 0) as sales')
            ->selectRaw('COALESCE(SUM(commission), 0) as commission')
            ->selectRaw('COALESCE(SUM(bonus), 0) as bonus')
            ->selectRaw('COALESCE(SUM(net), 0) as net')
            ->selectRaw('COUNT(*) as entries')
            ->first();

        // 3. Ledger balance
        $debit = (float) LedgerEntry::query()
            ->where('party_type', 'user')
            ->where('party_id', $user->id)
            ->where('type', 'debit')
            ->sum('amount');

        $credit = (float) LedgerEntry::query()
            ->where('party_type', 'user')
            ->where('party_id', $user->id)
            ->where('type', 'credit')
            ->sum('amount');

        $ledgerBalance = $credit - $debit;

        // 4. Pending advance requests count
        $pendingAdvances = AdvanceRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // 5. Recent entries (last 5)
        $recentEntries = DailyEntry::query()
            ->where('user_id', $user->id)
            ->orderByDesc('date')
            ->limit(5)
            ->get()
            ->map(fn(DailyEntry $entry) => [
                'id' => $entry->id,
                'date' => $entry->date?->toDateString(),
                'sales' => (float) $entry->sales,
                'commission' => (float) $entry->commission,
                'bonus' => (float) $entry->bonus,
                'net' => (float) $entry->net,
                'total_earnings' => (float) ($entry->commission + $entry->bonus),
                'note' => $entry->note,
                'source' => $entry->source,
                'transactions_count' => (int) $entry->transactions_count,
                'created_at' => $entry->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return $this->success([
            'today' => [
                'sales' => (float) ($todayStats->sales ?? 0),
                'commission' => (float) ($todayStats->commission ?? 0),
                'bonus' => (float) ($todayStats->bonus ?? 0),
                'total_earnings' => (float) (($todayStats->commission ?? 0) + ($todayStats->bonus ?? 0)),
                'entries' => (int) ($todayStats->entries ?? 0),
            ],
            'month' => [
                'sales' => (float) ($monthStats->sales ?? 0),
                'commission' => (float) ($monthStats->commission ?? 0),
                'bonus' => (float) ($monthStats->bonus ?? 0),
                'total_earnings' => (float) (($monthStats->commission ?? 0) + ($monthStats->bonus ?? 0)),
                'entries' => (int) ($monthStats->entries ?? 0),
            ],
            'ledger_balance' => (float) $ledgerBalance,
            'pending_advances' => (int) $pendingAdvances,
            'recent_entries' => $recentEntries,
        ]);
    }

    /**
     * GET /barber/history?period=week&search=...&page=1&per_page=50
     * Returns paginated entries + stats for the given period.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $period = $request->query('period', 'week');
        $search = $request->query('search', '');

        $today = now();
        $dateFrom = null;
        $dateTo = $today->toDateString();

        switch ($period) {
            case 'today':
                $dateFrom = $today->toDateString();
                break;
            case 'week':
                $dateFrom = $today->copy()->subDays(7)->toDateString();
                break;
            case 'month':
                $dateFrom = $today->copy()->subMonth()->toDateString();
                break;
            case 'all':
            default:
                $dateTo = null;
                break;
        }

        // Build query
        $query = DailyEntry::query()
            ->where('user_id', $user->id);

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('note', 'like', "%{$search}%")
                    ->orWhere('sales', $search)
                    ->orWhere('date', 'like', "%{$search}%");
            });
        }

        // Get stats from the SAME query (before pagination)
        $statsQuery = clone $query;
        $statsResult = $statsQuery
            ->selectRaw('COUNT(*) as total_entries')
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(commission), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(bonus), 0) as total_bonus')
            ->first();

        $totalEntries = (int) ($statsResult->total_entries ?? 0);
        $totalSales = (float) ($statsResult->total_sales ?? 0);
        $totalCommission = (float) ($statsResult->total_commission ?? 0);
        $totalBonus = (float) ($statsResult->total_bonus ?? 0);
        $totalEarnings = $totalCommission + $totalBonus;

        // Get paginated entries
        $perPage = min((int) $request->query('per_page', 50), 100);
        $entries = $query->orderByDesc('date')
            ->paginate($perPage);

        $items = $entries->getCollection()->map(fn(DailyEntry $entry) => [
            'id' => $entry->id,
            'date' => $entry->date?->toDateString(),
            'sales' => (float) $entry->sales,
            'commission' => (float) $entry->commission,
            'bonus' => (float) $entry->bonus,
            'total_earnings' => (float) ($entry->commission + $entry->bonus),
            'net' => (float) $entry->net,
            'note' => $entry->note,
            'source' => $entry->source,
            'transactions_count' => (int) $entry->transactions_count,
            'created_at' => $entry->created_at?->toIso8601String(),
        ])->values()->all();

        return $this->success([
            'stats' => [
                'total_entries' => $totalEntries,
                'total_sales' => $totalSales,
                'total_earnings' => $totalEarnings,
                'average_per_entry' => $totalEntries > 0 ? round($totalSales / $totalEntries) : 0,
            ],
            'entries' => $items,
            'pagination' => [
                'total' => $entries->total(),
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
            ],
        ]);
    }

    /**
     * GET /barber/reports?period=month
     * Returns current stats, previous period comparison, weekly chart, best/worst days.
     */
    public function reports(Request $request)
    {
        $user = $request->user();
        $period = $request->query('period', 'month');

        $today = Carbon::today();
        $todayStr = $today->toDateString();

        // Compute date ranges for current and previous periods
        switch ($period) {
            case 'week':
                $from = $today->copy()->subDays(6);
                $prevTo = $from->copy()->subDay();
                $prevFrom = $prevTo->copy()->subDays(6);
                break;
            case 'quarter':
                $from = $today->copy()->subDays(89);
                $prevTo = $from->copy()->subDay();
                $prevFrom = $prevTo->copy()->subDays(89);
                break;
            case 'year':
                $from = Carbon::create($today->year, 1, 1);
                $prevFrom = Carbon::create($today->year - 1, 1, 1);
                $prevTo = Carbon::create($today->year - 1, 12, 31);
                break;
            case 'month':
            default:
                $from = $today->copy()->startOfMonth();
                $prevFrom = $today->copy()->subMonth()->startOfMonth();
                $prevTo = $today->copy()->subMonth()->endOfMonth();
                break;
        }

        $fromStr = $from->toDateString();
        $prevFromStr = $prevFrom->toDateString();
        $prevToStr = $prevTo->toDateString();

        // Helper function to get period stats
        $getStats = function (string $dateFrom, string $dateTo) use ($user) {
            $entries = DailyEntry::query()
                ->where('user_id', $user->id)
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->orderBy('date')
                ->get();

            $count = $entries->count();
            $sales = (float) $entries->sum('sales');
            $commission = (float) $entries->sum('commission');
            $bonus = (float) $entries->sum('bonus');
            $totalEarnings = $commission + $bonus;
            $periodDays = (int) (Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1);

            $best = $count > 0 ? $entries->sortByDesc('sales')->first() : null;
            $worst = $count > 0 ? $entries->sortBy('sales')->first() : null;

            return [
                'totals' => [
                    'sales' => $sales,
                    'cash' => (float) $entries->sum('cash'),
                    'expense' => (float) $entries->sum('expense'),
                    'net' => (float) $entries->sum('net'),
                    'commission' => $commission,
                    'bonus' => $bonus,
                    'total_earnings' => $totalEarnings,
                    'entries' => $count,
                ],
                'averages' => [
                    'daily_sales' => $count > 0 ? round($sales / $count, 2) : 0,
                    'daily_commission' => $count > 0 ? round($commission / $count, 2) : 0,
                    'daily_bonus' => $count > 0 ? round($bonus / $count, 2) : 0,
                ],
                'best_day' => $best ? [
                    'date' => $best->date?->toDateString(),
                    'sales' => (float) $best->sales,
                    'net' => (float) $best->net,
                    'commission' => (float) $best->commission,
                ] : null,
                'worst_day' => $worst ? [
                    'date' => $worst->date?->toDateString(),
                    'sales' => (float) $worst->sales,
                    'net' => (float) $worst->net,
                    'commission' => (float) $worst->commission,
                ] : null,
                'working_days' => $count,
                'zero_days' => max($periodDays - $count, 0),
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                    'days' => $periodDays,
                ],
            ];
        };

        $currentStats = $getStats($fromStr, $todayStr);
        $previousStats = $getStats($prevFromStr, $prevToStr);

        // Weekly chart data (last 7 days)
        $weekAgo = $today->copy()->subDays(6)->toDateString();
        $weekEntries = DailyEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$weekAgo, $todayStr])
            ->get()
            ->groupBy(fn($e) => $e->date->toDateString());

        $dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        $weeklyChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $dateStr = $d->toDateString();
            $dayEntries = $weekEntries->get($dateStr);
            $weeklyChart[] = [
                'day' => $dayNames[$d->dayOfWeek],
                'date' => $dateStr,
                'amount' => $dayEntries ? (float) $dayEntries->sum('sales') : 0,
                'is_today' => $dateStr === $todayStr,
            ];
        }

        return $this->success([
            'current' => $currentStats,
            'previous' => $previousStats,
            'weekly_chart' => $weeklyChart,
        ]);
    }

    /**
     * GET /barber/profile
     * Returns user info + all-time stats.
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        // All-time stats
        $stats = DailyEntry::query()
            ->where('user_id', $user->id)
            ->selectRaw('COUNT(*) as total_entries')
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(commission), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(bonus), 0) as total_bonus')
            ->first();

        $totalEarnings = (float) ($stats->total_commission ?? 0) + (float) ($stats->total_bonus ?? 0);

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'commission_rate' => $user->commission_rate !== null ? (float) $user->commission_rate : null,
                'hire_date' => $user->hire_date,
                'branch' => $user->branch ? [
                    'id' => $user->branch->id,
                    'name' => $user->branch->name,
                ] : null,
            ],
            'stats' => [
                'total_entries' => (int) ($stats->total_entries ?? 0),
                'total_sales' => (float) ($stats->total_sales ?? 0),
                'total_earnings' => $totalEarnings,
            ],
        ]);
    }
}
