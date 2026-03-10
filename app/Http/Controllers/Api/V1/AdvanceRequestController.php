<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AdvanceRequest;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdvanceRequestController extends ApiController
{
    public function index(Request $request)
    {
        if (!$this->isAdminDashboardRole($request->user())) {
            $this->requirePermissionOrSelf('ViewAny:AdvanceRequest', $request->string('user_id')->toString() ?: null);
        }

        $query = $this->applyIndexFilters(AdvanceRequest::query()->with(['user', 'branch']), $request);
        $summaryQuery = $this->applyIndexFilters(AdvanceRequest::query(), $request);

        $stats = [
            'pending' => (clone $summaryQuery)->where('status', 'pending')->count(),
            'approved' => (clone $summaryQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $summaryQuery)->where('status', 'rejected')->count(),
            'total_amount' => (float) ((clone $summaryQuery)->where('status', 'approved')->sum('amount') ?? 0),
        ];

        $paginator = $query->orderByDesc('requested_at')->paginate($this->perPage());

        $items = $paginator->getCollection()->map(function (AdvanceRequest $requestModel) {
            return [
                'id' => $requestModel->id,
                'user' => $requestModel->user ? [
                    'id' => $requestModel->user->id,
                    'name' => $requestModel->user->name,
                    'phone' => $requestModel->user->phone,
                ] : null,
                'branch' => $requestModel->branch ? [
                    'id' => $requestModel->branch->id,
                    'name' => $requestModel->branch->name,
                ] : null,
                'amount' => (float) $requestModel->amount,
                'reason' => $requestModel->reason,
                'status' => $requestModel->status,
                'requested_at' => $requestModel->requested_at?->toIso8601String(),
                'reviewed_at' => $requestModel->processed_at?->toIso8601String(),
                'reviewer_notes' => $requestModel->decision_notes ?? $requestModel->rejection_reason,
                'attachment_url' => $requestModel->attachment_url,
            ];
        })->values()->all();

        return $this->paginated($paginator, $items, [], [
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $this->requirePermissionOrSelf('Create:AdvanceRequest', $request->input('user_id') ?? $request->user()?->id);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['nullable', 'string'],
            'attachment' => ['nullable', 'string'],
            'user_id' => [
                'nullable',
                'uuid',
                Rule::exists('users', 'id')->where(fn($query) => $query->whereIn('role', User::employeeRoles())),
            ],
        ]);

        $employee = null;

        if (!empty($data['user_id'])) {
            $employee = User::query()->find($data['user_id']);
        } else {
            $employee = User::query()->where('phone', $request->user()->phone)->first();
        }

        if (!$employee) {
            return $this->error('VALIDATION_ERROR', 'تعذر تحديد الموظف لهذا الطلب', 422);
        }

        $attachmentUrl = null;

        if (!empty($data['attachment'])) {
            $attachmentUrl = $this->storeAttachment($data['attachment']);
            if (!$attachmentUrl) {
                return $this->error('VALIDATION_ERROR', 'الملف المرفق غير صالح', 422);
            }
        }

        $advance = AdvanceRequest::create([
            'user_id' => $employee->id,
            'branch_id' => $employee->branch_id,
            'amount' => $data['amount'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
            'attachment_url' => $attachmentUrl,
        ]);

        return $this->success([
            'id' => $advance->id,
            'amount' => (float) $advance->amount,
            'status' => $advance->status,
            'requested_at' => $advance->requested_at?->toIso8601String(),
        ], 'تم تقديم الطلب بنجاح', 201);
    }

    public function approve(Request $request, AdvanceRequest $advanceRequest)
    {
        $this->requireAdminOrPermission('Update:AdvanceRequest');

        if ($advanceRequest->status !== 'pending') {
            return $this->error('VALIDATION_ERROR', 'لا يمكن الموافقة على هذا الطلب', 409);
        }

        $data = $request->validate([
            'decision_notes' => ['nullable', 'string'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'in:cash,bank_transfer,check,deduction'],
        ]);

        $ledger = DB::transaction(function () use ($advanceRequest, $data, $request) {
            $entry = LedgerEntry::create([
                'party_type' => 'user',
                'party_id' => $advanceRequest->user_id,
                'date' => $data['payment_date'] ?? now()->toDateString(),
                'type' => 'debit',
                'amount' => $advanceRequest->amount,
                'description' => 'Advance request',
                'category' => 'advance',
                'source' => 'advance_request',
                'reference_id' => $advanceRequest->id,
                'reference_type' => 'advance_request',
                'payment_method' => $data['payment_method'] ?? null,
                'status' => 'confirmed',
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $advanceRequest->forceFill([
                'status' => 'approved',
                'processed_at' => now(),
                'processed_by' => $request->user()->id,
                'decision_notes' => $data['decision_notes'] ?? null,
                'payment_date' => $data['payment_date'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'ledger_entry_id' => $entry->id,
            ])->save();

            return $entry;
        });

        return $this->success([
            'id' => $advanceRequest->id,
            'status' => $advanceRequest->status,
            'processed_at' => $advanceRequest->processed_at?->toIso8601String(),
            'ledger_entry_id' => $ledger->id,
        ], 'تمت الموافقة على الطلب');
    }

    public function reject(Request $request, AdvanceRequest $advanceRequest)
    {
        $this->requireAdminOrPermission('Update:AdvanceRequest');

        if ($advanceRequest->status !== 'pending') {
            return $this->error('VALIDATION_ERROR', 'لا يمكن رفض هذا الطلب', 409);
        }

        $data = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $advanceRequest->forceFill([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => $request->user()->id,
            'rejection_reason' => $data['rejection_reason'],
        ])->save();

        return $this->success([
            'id' => $advanceRequest->id,
            'status' => $advanceRequest->status,
            'processed_at' => $advanceRequest->processed_at?->toIso8601String(),
        ], 'تم رفض الطلب');
    }

    private function applyIndexFilters($query, Request $request)
    {
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->string('user_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->string('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('requested_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('requested_at', '<=', $request->date('date_to'));
        }

        return $query;
    }

    private function storeAttachment(string $base64): ?string
    {
        $payload = $base64;
        $extension = 'png';

        if (str_contains($base64, ';base64,')) {
            [$meta, $payload] = explode(';base64,', $base64, 2);
            if (str_contains($meta, 'image/jpeg')) {
                $extension = 'jpg';
            } elseif (str_contains($meta, 'image/png')) {
                $extension = 'png';
            }
        }

        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            return null;
        }

        $path = 'advance-requests/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($path, $decoded);

        return Storage::disk('public')->url($path);
    }
}
