<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\User;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LedgerEntryController extends ApiController
{
    public function index(Request $request)
    {
        $this->requirePermission('ViewAny:LedgerEntry');

        $query = LedgerEntry::query()->with('createdBy');

        if ($request->filled('party_type')) {
            $query->where('party_type', $request->string('party_type'));
        }

        if ($request->filled('party_id')) {
            $query->where('party_id', $request->string('party_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        $paginator = $query->orderByDesc('date')->paginate($this->perPage());

        $entries = $paginator->getCollection();
        $partyMap = $this->resolveParties($entries);

        $totals = (clone $query)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) as total_debit")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) as total_credit")
            ->first();

        $balance = (float) ($totals->total_credit ?? 0) - (float) ($totals->total_debit ?? 0);

        $items = $entries->map(function (LedgerEntry $entry) use ($partyMap) {
            $party = $partyMap[$entry->party_type][$entry->party_id] ?? null;

            return [
                'id' => $entry->id,
                'date' => $entry->date?->toDateString(),
                'party_type' => $entry->party_type,
                'party' => $party,
                'type' => $entry->type,
                'amount' => (float) $entry->amount,
                'description' => $entry->description,
                'category' => $entry->category,
                'source' => $entry->source,
                'reference_id' => $entry->reference_id,
                'payment_method' => $entry->payment_method,
                'status' => $entry->status,
                'created_by' => $entry->createdBy ? [
                    'id' => $entry->createdBy->id,
                    'name' => $entry->createdBy->name,
                ] : null,
                'created_at' => $entry->created_at?->toIso8601String(),
            ];
        })->values()->all();

        return $this->paginated($paginator, $items, [], [
            'balance' => [
                'total_debit' => (float) ($totals->total_debit ?? 0),
                'total_credit' => (float) ($totals->total_credit ?? 0),
                'balance' => $balance,
                'balance_label' => $this->formatBalanceLabel($balance),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->requirePermission('Create:LedgerEntry');

        $data = $request->validate([
            'party_type' => ['required', 'string', 'in:employee,branch,supplier,customer'],
            'party_id' => ['required', 'uuid'],
            'date' => ['required', 'date'],
            'type' => ['required', 'string', 'in:debit,credit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string'],
            'category' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string', 'in:cash,bank_transfer,check,other'],
        ]);

        if ($data['party_type'] === 'employee' && !User::query()->whereKey($data['party_id'])->whereIn('role', User::employeeRoles())->exists()) {
            return $this->error('VALIDATION_ERROR', 'الطرف غير موجود', 422);
        }

        if ($data['party_type'] === 'branch' && !Branch::query()->whereKey($data['party_id'])->exists()) {
            return $this->error('VALIDATION_ERROR', 'الطرف غير موجود', 422);
        }

        $entry = LedgerEntry::create([
            'party_type' => $data['party_type'],
            'party_id' => $data['party_id'],
            'date' => $data['date'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'category' => $data['category'] ?? null,
            'source' => 'manual',
            'payment_method' => $data['payment_method'] ?? null,
            'status' => 'confirmed',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $balance = $this->calculateBalance($data['party_type'], $data['party_id']);

        return $this->success([
            'id' => $entry->id,
            'date' => $entry->date?->toDateString(),
            'type' => $entry->type,
            'amount' => (float) $entry->amount,
            'new_balance' => $balance['balance'],
            'created_at' => $entry->created_at?->toIso8601String(),
        ], 'تم إضافة القيد بنجاح', 201);
    }

    public function balance(string $party_type, string $party_id)
    {
        $this->requirePermission('ViewAny:LedgerEntry');

        if (!in_array($party_type, ['employee', 'branch', 'supplier', 'customer'], true)) {
            return $this->error('VALIDATION_ERROR', 'نوع الطرف غير صالح', 422);
        }

        $balance = $this->calculateBalance($party_type, $party_id);
        $party = $balance['party'];

        return $this->success([
            'party_type' => $party_type,
            'party' => $party,
            'balance' => $balance['balance'],
            'balance_label' => $this->formatBalanceLabel($balance['balance']),
            'total_debit' => $balance['total_debit'],
            'total_credit' => $balance['total_credit'],
            'entries_count' => $balance['entries_count'],
            'last_entry_date' => $balance['last_entry_date'],
        ]);
    }

    private function resolveParties($entries): array
    {
        $employeeIds = $entries->where('party_type', 'employee')->pluck('party_id')->unique()->all();
        $branchIds = $entries->where('party_type', 'branch')->pluck('party_id')->unique()->all();

        $employees = User::query()->whereIn('id', $employeeIds)->get()->keyBy('id');
        $branches = Branch::query()->whereIn('id', $branchIds)->get()->keyBy('id');

        $partyMap = [
            'employee' => [],
            'branch' => [],
        ];

        foreach ($employees as $employee) {
            $partyMap['employee'][$employee->id] = [
                'id' => $employee->id,
                'name' => $employee->name,
            ];
        }

        foreach ($branches as $branch) {
            $partyMap['branch'][$branch->id] = [
                'id' => $branch->id,
                'name' => $branch->name,
            ];
        }

        return $partyMap;
    }

    private function calculateBalance(string $partyType, string $partyId): array
    {
        $totals = LedgerEntry::query()
            ->where('party_type', $partyType)
            ->where('party_id', $partyId)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) as total_debit")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) as total_credit")
            ->selectRaw('COUNT(*) as entries_count')
            ->selectRaw('MAX(date) as last_entry_date')
            ->first();

        $balance = (float) ($totals->total_credit ?? 0) - (float) ($totals->total_debit ?? 0);

        $party = null;

        if ($partyType === 'employee') {
            $employee = User::query()->find($partyId);
            $party = $employee ? ['id' => $employee->id, 'name' => $employee->name] : null;
        } elseif ($partyType === 'branch') {
            $branch = Branch::query()->find($partyId);
            $party = $branch ? ['id' => $branch->id, 'name' => $branch->name] : null;
        }

        return [
            'party' => $party,
            'balance' => $balance,
            'total_debit' => (float) ($totals->total_debit ?? 0),
            'total_credit' => (float) ($totals->total_credit ?? 0),
            'entries_count' => (int) ($totals->entries_count ?? 0),
            'last_entry_date' => $totals->last_entry_date,
        ];
    }

    private function formatBalanceLabel(float $balance): string
    {
        if ($balance < 0) {
            return 'عليه ' . number_format(abs($balance), 2) . ' ريال';
        }

        if ($balance > 0) {
            return 'له ' . number_format($balance, 2) . ' ريال';
        }

        return 'متوازن';
    }
}
