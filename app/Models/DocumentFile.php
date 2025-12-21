<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentFile extends Model
{
    use HasFactory, HasUuids, LogsModelActivity;

    public const CREATED_AT = 'uploaded_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'document_id',
        'name',
        'size',
        'mime_type',
        'file_url',
        'storage_provider',
        'version',
        'is_current',
        'uploaded_at',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'version' => 'integer',
            'is_current' => 'boolean',
            'uploaded_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
