<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LedgerEntryController extends ApiController
{
    public function index(Request $request)
    {
        $this->requireAdminOrPermission('ViewAny:LedgerEntry');

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
                'party_id' => $entry->party_id,
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
        $this->requireAdminOrPermission('Create:LedgerEntry');

        $data = $request->validate([
            'party_type' => ['required', 'string', 'in:user,branch'],
            'party_id' => ['required', 'uuid'],
            'date' => ['required', 'date'],
            'type' => ['required', 'string', 'in:debit,credit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string'],
            'category' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string', 'in:cash,bank_transfer,check,other'],
        ]);

        $this->validatePartyExists($data['party_type'], $data['party_id']);

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
            'party_type' => $entry->party_type,
            'party_id' => $entry->party_id,
            'type' => $entry->type,
            'amount' => (float) $entry->amount,
            'new_balance' => $balance['balance'],
            'created_at' => $entry->created_at?->toIso8601String(),
        ], 'تم إضافة القيد بنجاح', 201);
    }

    public function update(Request $request, LedgerEntry $ledgerEntry)
    {
        $this->requireAdminOrPermission('Update:LedgerEntry');

        if ($ledgerEntry->source !== 'manual') {
            return $this->error('LEDGER_ENTRY_LOCKED', 'لا يمكن تعديل القيد التلقائي', 409, [
                'source' => $ledgerEntry->source,
            ]);
        }

        $data = $request->validate([
            'party_type' => ['sometimes', 'string', 'in:user,branch'],
            'party_id' => ['sometimes', 'uuid'],
            'date' => ['sometimes', 'date'],
            'type' => ['sometimes', 'string', 'in:debit,credit'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'description' => ['sometimes', 'string'],
            'category' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string', 'in:cash,bank_transfer,check,other'],
        ]);

        $partyType = $data['party_type'] ?? $ledgerEntry->party_type;
        $partyId = $data['party_id'] ?? $ledgerEntry->party_id;

        $this->validatePartyExists($partyType, $partyId);

        $ledgerEntry->forceFill([
            'party_type' => $partyType,
            'party_id' => $partyId,
            'date' => $data['date'] ?? $ledgerEntry->date?->toDateString(),
            'type' => $data['type'] ?? $ledgerEntry->type,
            'amount' => array_key_exists('amount', $data) ? $data['amount'] : $ledgerEntry->amount,
            'description' => $data['description'] ?? $ledgerEntry->description,
            'category' => array_key_exists('category', $data) ? $data['category'] : $ledgerEntry->category,
            'payment_method' => array_key_exists('payment_method', $data) ? $data['payment_method'] : $ledgerEntry->payment_method,
            'updated_by' => $request->user()->id,
        ])->save();

        $balance = $this->calculateBalance($ledgerEntry->party_type, $ledgerEntry->party_id);

        return $this->success([
            'id' => $ledgerEntry->id,
            'date' => $ledgerEntry->date?->toDateString(),
            'party_type' => $ledgerEntry->party_type,
            'party_id' => $ledgerEntry->party_id,
            'type' => $ledgerEntry->type,
            'amount' => (float) $ledgerEntry->amount,
            'description' => $ledgerEntry->description,
            'category' => $ledgerEntry->category,
            'payment_method' => $ledgerEntry->payment_method,
            'new_balance' => $balance['balance'],
            'updated_at' => $ledgerEntry->updated_at?->toIso8601String(),
        ], 'تم تحديث القيد بنجاح');
    }

    public function destroy(LedgerEntry $ledgerEntry)
    {
        $this->requireAdminOrPermission('Delete:LedgerEntry');

        if ($ledgerEntry->source !== 'manual') {
            return $this->error('LEDGER_ENTRY_LOCKED', 'لا يمكن حذف القيد التلقائي', 409, [
                'source' => $ledgerEntry->source,
            ]);
        }

        $ledgerEntry->delete();

        return $this->success(null, 'تم حذف القيد بنجاح');
    }

    public function balance(string $party_type, string $party_id)
    {
        $this->requireAdminOrPermission('ViewAny:LedgerEntry');

        if (!in_array($party_type, ['user', 'branch'], true)) {
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
        $userIds = $entries->where('party_type', 'user')->pluck('party_id')->unique()->all();
        $branchIds = $entries->where('party_type', 'branch')->pluck('party_id')->unique()->all();

        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');
        $branches = Branch::query()->whereIn('id', $branchIds)->get()->keyBy('id');

        $partyMap = [
            'user' => [],
            'branch' => [],
        ];

        foreach ($users as $user) {
            $partyMap['user'][$user->id] = [
                'id' => $user->id,
                'name' => $user->name,
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

        if ($partyType === 'user') {
            $user = User::query()->find($partyId);
            $party = $user ? ['id' => $user->id, 'name' => $user->name] : null;
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

    private function validatePartyExists(string $partyType, string $partyId): void
    {
        if ($partyType === 'user' && !User::query()->whereKey($partyId)->exists()) {
            throw ValidationException::withMessages([
                'party_id' => ['الموظف غير موجود'],
            ]);
        }

        if ($partyType === 'branch' && !Branch::query()->whereKey($partyId)->exists()) {
            throw ValidationException::withMessages([
                'party_id' => ['الفرع غير موجود'],
            ]);
        }
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
