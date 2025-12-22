<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentController extends ApiController
{
    public function index(Request $request)
    {
        $this->requirePermission('ViewAny:Document');

        $query = Document::query()->withCount('files');

        if ($request->filled('owner_type')) {
            $query->where('owner_type', $request->string('owner_type'));
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->string('owner_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->boolean('expiring_soon')) {
            $query->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', now()->addDays(30));
        }

        $paginator = $query->orderByDesc('created_at')->paginate($this->perPage());
        $documents = $paginator->getCollection();
        $owners = $this->resolveOwners($documents);

        $items = $documents->map(function (Document $document) use ($owners) {
            $owner = $owners[$document->owner_type][$document->owner_id] ?? null;

            return [
                'id' => $document->id,
                'owner_type' => $document->owner_type,
                'owner' => $owner,
                'type' => $document->type,
                'number' => $document->number,
                'title' => $document->title,
                'issue_date' => $document->issue_date?->toDateString(),
                'expiry_date' => $document->expiry_date?->toDateString(),
                'status' => $document->status,
                'days_remaining' => $document->days_remaining,
                'files_count' => $document->files_count,
                'created_at' => $document->created_at?->toIso8601String(),
            ];
        })->values()->all();

        return $this->paginated($paginator, $items);
    }

    public function store(Request $request)
    {
        $this->requirePermission('Create:Document');

        $data = $request->validate([
            'owner_type' => ['required', 'string', Rule::in(['employee', 'branch', 'company'])],
            'owner_id' => ['required', 'uuid'],
            'type' => ['required', 'string', 'max:50'],
            'number' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:200'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'notify_before_days' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:5120'],
        ]);

        if ($data['owner_type'] === 'employee' && ! Employee::query()->whereKey($data['owner_id'])->exists()) {
            return $this->error('VALIDATION_ERROR', 'المالك غير موجود', 422);
        }

        if ($data['owner_type'] === 'branch' && ! Branch::query()->whereKey($data['owner_id'])->exists()) {
            return $this->error('VALIDATION_ERROR', 'الفرع غير موجود', 422);
        }

        $document = Document::create([
            'owner_type' => $data['owner_type'],
            'owner_id' => $data['owner_id'],
            'type' => $data['type'],
            'number' => $data['number'] ?? null,
            'title' => $data['title'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'notify_before_days' => $data['notify_before_days'] ?? 30,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $files = $request->file('files');

        if ($files) {
            $fileList = is_array($files) ? $files : [$files];

            foreach ($fileList as $file) {
                $this->storeDocumentFile($document, $file, $request->user()->id);
            }
        }

        return $this->success([
            'id' => $document->id,
            'type' => $document->type,
            'number' => $document->number,
            'expiry_date' => $document->expiry_date?->toDateString(),
            'status' => $document->status,
            'days_remaining' => $document->days_remaining,
            'files_count' => $document->files()->count(),
        ], 'تم إضافة الوثيقة بنجاح', 201);
    }

    public function update(Request $request, Document $document)
    {
        $this->requirePermission('Update:Document');

        $data = $request->validate([
            'expiry_date' => ['nullable', 'date'],
            'notify_before_days' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $document->forceFill($data);
        $document->updated_by = $request->user()->id;
        $document->save();

        return $this->success([
            'id' => $document->id,
            'expiry_date' => $document->expiry_date?->toDateString(),
            'status' => $document->status,
            'days_remaining' => $document->days_remaining,
        ], 'تم تحديث الوثيقة بنجاح');
    }

    public function addFile(Request $request, Document $document)
    {
        $this->requirePermission('Update:Document');

        $data = $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $fileModel = $this->storeDocumentFile($document, $data['file'], $request->user()->id);

        return $this->success([
            'id' => $fileModel->id,
            'name' => $fileModel->name,
            'size' => $fileModel->size,
            'mime_type' => $fileModel->mime_type,
            'file_url' => $fileModel->file_url,
            'uploaded_at' => $fileModel->uploaded_at?->toIso8601String(),
        ], 'تم رفع الملف بنجاح', 201);
    }

    public function expiringSoon(Request $request)
    {
        $this->requirePermission('ViewAny:Document');

        $days = (int) $request->query('days', 30);
        $days = $days < 1 ? 30 : $days;

        $query = Document::query()
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days));

        if ($request->filled('owner_type')) {
            $query->where('owner_type', $request->string('owner_type'));
        }

        $documents = $query->get();
        $owners = $this->resolveOwners($documents);

        $urgent = [];
        $near = [];

        foreach ($documents as $document) {
            $payload = [
                'id' => $document->id,
                'owner' => $owners[$document->owner_type][$document->owner_id] ?? null,
                'type' => $document->type,
                'expiry_date' => $document->expiry_date?->toDateString(),
                'days_remaining' => $document->days_remaining,
            ];

            if (in_array($document->status, ['urgent', 'expired'], true)) {
                $urgent[] = $payload;
            } else {
                $near[] = $payload;
            }
        }

        return $this->success([
            'urgent' => $urgent,
            'near' => $near,
            'counts' => [
                'urgent' => count($urgent),
                'near' => count($near),
                'total' => count($urgent) + count($near),
            ],
        ]);
    }

    private function storeDocumentFile(Document $document, $file, string $userId): DocumentFile
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        Storage::disk('public')->putFileAs('documents', $file, $filename);

        $nextVersion = ($document->files()->max('version') ?? 0) + 1;
        $document->files()->update(['is_current' => false]);

        return DocumentFile::create([
            'document_id' => $document->id,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_url' => Storage::disk('public')->url('documents/'.$filename),
            'storage_provider' => 'local',
            'version' => $nextVersion,
            'is_current' => true,
            'uploaded_by' => $userId,
        ]);
    }

    private function resolveOwners($documents): array
    {
        $employeeIds = $documents->where('owner_type', 'employee')->pluck('owner_id')->unique()->all();
        $branchIds = $documents->where('owner_type', 'branch')->pluck('owner_id')->unique()->all();

        $employees = Employee::query()->whereIn('id', $employeeIds)->get()->keyBy('id');
        $branches = Branch::query()->whereIn('id', $branchIds)->get()->keyBy('id');

        $owners = [
            'employee' => [],
            'branch' => [],
            'company' => [],
        ];

        foreach ($employees as $employee) {
            $owners['employee'][$employee->id] = [
                'id' => $employee->id,
                'name' => $employee->name,
            ];
        }

        foreach ($branches as $branch) {
            $owners['branch'][$branch->id] = [
                'id' => $branch->id,
                'name' => $branch->name,
            ];
        }

        return $owners;
    }
}
