<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\DailyEntry;
use App\Models\DayClosure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DayClosureController extends ApiController
{
    public function index(Request $request)
    {
        $this->requirePermission('ViewAny:DayClosure');

        $query = DayClosure::query()->with(['branch', 'closedBy']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->string('branch_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        $paginator = $query->orderByDesc('date')->paginate($this->perPage());

        $items = $paginator->getCollection()->map(function (DayClosure $closure) {
            return [
                'id' => $closure->id,
                'date' => $closure->date?->toDateString(),
                'branch' => $closure->branch ? [
                    'id' => $closure->branch->id,
                    'name' => $closure->branch->name,
                ] : null,
                'total_sales' => (float) $closure->total_sales,
                'total_cash' => (float) $closure->total_cash,
                'total_expense' => (float) $closure->total_expense,
                'total_net' => (float) $closure->total_net,
                'total_commission' => (float) $closure->total_commission,
                'total_bonus' => (float) $closure->total_bonus,
                'entries_count' => (int) $closure->entries_count,
                'employees_count' => (int) $closure->employees_count,
                'closed_by' => $closure->closedBy ? [
                    'id' => $closure->closedBy->id,
                    'name' => $closure->closedBy->name,
                ] : null,
                'closed_at' => $closure->closed_at?->toIso8601String(),
                'pdf_url' => $closure->pdf_url,
                'pdf_generated_at' => $closure->pdf_generated_at?->toIso8601String(),
            ];
        })->values()->all();

        return $this->paginated($paginator, $items);
    }

    public function store(Request $request)
    {
        $this->requirePermission('Create:DayClosure');

        $data = $request->validate([
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $existing = DayClosure::query()
            ->where('branch_id', $data['branch_id'])
            ->whereDate('date', $data['date'])
            ->first();

        if ($existing) {
            return $this->error('DAY_ALREADY_CLOSED', 'هذا اليوم مغلق مسبقاً', 409, [
                'closure_id' => $existing->id,
                'closed_at' => $existing->closed_at?->toIso8601String(),
            ]);
        }

        $entries = DailyEntry::query()
            ->where('branch_id', $data['branch_id'])
            ->whereDate('date', $data['date'])
            ->get();

        if ($entries->isEmpty()) {
            return $this->error('NO_ENTRIES_TO_CLOSE', 'لا توجد إدخالات لهذا اليوم', 400, [
                'date' => $data['date'],
                'branch_id' => $data['branch_id'],
            ]);
        }

        $summary = [
            'total_sales' => (float) $entries->sum('sales'),
            'total_cash' => (float) $entries->sum('cash'),
            'total_expense' => (float) $entries->sum('expense'),
            'total_net' => (float) $entries->sum('net'),
            'total_commission' => (float) $entries->sum('commission'),
            'total_bonus' => (float) $entries->sum('bonus'),
            'entries_count' => $entries->count(),
            'employees_count' => $entries->pluck('employee_id')->unique()->count(),
        ];

        $closure = DB::transaction(function () use ($data, $summary, $request) {
            $created = DayClosure::create([
                'branch_id' => $data['branch_id'],
                'date' => $data['date'],
                'total_sales' => $summary['total_sales'],
                'total_cash' => $summary['total_cash'],
                'total_expense' => $summary['total_expense'],
                'total_net' => $summary['total_net'],
                'total_commission' => $summary['total_commission'],
                'total_bonus' => $summary['total_bonus'],
                'entries_count' => $summary['entries_count'],
                'employees_count' => $summary['employees_count'],
                'closed_by' => $request->user()->id,
                'closed_at' => now(),
                'notes' => $data['notes'] ?? null,
                'pdf_url' => null,
            ]);

            $created->forceFill([
                'pdf_url' => url("/api/v1/day-closures/{$created->id}/pdf"),
            ])->save();

            DailyEntry::query()
                ->where('branch_id', $data['branch_id'])
                ->whereDate('date', $data['date'])
                ->update([
                    'is_locked' => true,
                    'locked_at' => now(),
                    'locked_by' => $request->user()->id,
                ]);

            return $created;
        });

        return $this->success([
            'id' => $closure->id,
            'date' => $closure->date?->toDateString(),
            'branch_id' => $closure->branch_id,
            'summary' => $summary,
            'pdf_url' => $closure->pdf_url,
            'closed_at' => $closure->closed_at?->toIso8601String(),
        ], 'تم إغلاق اليوم بنجاح', 201);
    }

    public function show(DayClosure $dayClosure)
    {
        $this->requirePermission('View:DayClosure');

        $dayClosure->load(['branch', 'closedBy']);

        $entries = DailyEntry::query()
            ->where('branch_id', $dayClosure->branch_id)
            ->whereDate('date', $dayClosure->date)
            ->select('employee_id', DB::raw('COALESCE(SUM(sales), 0) as sales'), DB::raw('COALESCE(SUM(commission), 0) as commission'), DB::raw('COALESCE(SUM(bonus), 0) as bonus'))
            ->groupBy('employee_id')
            ->get();

        $employees = $entries->pluck('employee_id')->all();
        $employeeNames = \App\Models\Employee::query()
            ->whereIn('id', $employees)
            ->pluck('name', 'id');

        return $this->success([
            'id' => $dayClosure->id,
            'date' => $dayClosure->date?->toDateString(),
            'branch' => $dayClosure->branch ? [
                'id' => $dayClosure->branch->id,
                'name' => $dayClosure->branch->name,
                'code' => $dayClosure->branch->code,
            ] : null,
            'summary' => [
                'total_sales' => (float) $dayClosure->total_sales,
                'total_cash' => (float) $dayClosure->total_cash,
                'total_expense' => (float) $dayClosure->total_expense,
                'total_net' => (float) $dayClosure->total_net,
                'total_commission' => (float) $dayClosure->total_commission,
                'total_bonus' => (float) $dayClosure->total_bonus,
                'entries_count' => (int) $dayClosure->entries_count,
                'employees_count' => (int) $dayClosure->employees_count,
            ],
            'entries' => $entries->map(function ($entry) use ($employeeNames) {
                return [
                    'employee_name' => $employeeNames[$entry->employee_id] ?? null,
                    'sales' => (float) $entry->sales,
                    'commission' => (float) $entry->commission,
                    'bonus' => (float) $entry->bonus,
                ];
            })->values()->all(),
            'closed_by' => $dayClosure->closedBy ? [
                'id' => $dayClosure->closedBy->id,
                'name' => $dayClosure->closedBy->name,
            ] : null,
            'closed_at' => $dayClosure->closed_at?->toIso8601String(),
            'pdf_url' => $dayClosure->pdf_url,
            'pdf_generated_at' => $dayClosure->pdf_generated_at?->toIso8601String(),
            'notes' => $dayClosure->notes,
        ]);
    }

    public function pdf(DayClosure $dayClosure)
    {
        $this->requirePermission('View:DayClosure');

        if (! $dayClosure->pdf_generated_at) {
            $dayClosure->forceFill([
                'pdf_generated_at' => now(),
            ])->save();
        }

        $content = $this->buildPdf([
            'Salon Day Closure',
            'Branch: '.$dayClosure->branch_id,
            'Date: '.$dayClosure->date?->toDateString(),
            'Total Sales: '.$dayClosure->total_sales,
            'Total Net: '.$dayClosure->total_net,
            'Entries: '.$dayClosure->entries_count,
        ]);

        return response($content, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="closure-'.$dayClosure->date?->toDateString().'.pdf"');
    }

    private function buildPdf(array $lines): string
    {
        $escapedLines = array_map(function (string $line) {
            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
        }, $lines);

        $stream = "BT\n/F1 12 Tf\n50 750 Td\n";
        $first = true;
        foreach ($escapedLines as $line) {
            if (! $first) {
                $stream .= "T*\n";
            }
            $stream .= "({$line}) Tj\n";
            $first = false;
        }
        $stream .= "ET";

        $objects = [
            "<< /Type /Catalog /Pages 2 0 R >>",
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>",
            "<< /Length ".strlen($stream)." >>\nstream\n{$stream}\nendstream",
            "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            if ($offset === 0) {
                continue;
            }
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
