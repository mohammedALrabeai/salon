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
}
