<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'type',
        'number',
        'title',
        'issue_date',
        'expiry_date',
        'notify_before_days',
        'last_notified_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'days_remaining' => 'integer',
            'notify_before_days' => 'integer',
            'last_notified_at' => 'datetime',
        ];
    }

    public function branchOwner(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'owner_id')->where('owner_type', 'branch');
    }

    public function employeeOwner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_id')->where('owner_type', 'employee');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
