<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, HasUuids, LogsModelActivity, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'phone',
        'email',
        'national_id',
        'passport_number',
        'role',
        'hire_date',
        'termination_date',
        'employment_type',
        'commission_rate',
        'commission_type',
        'base_salary',
        'status',
        'avatar_url',
        'bio',
        'skills',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'termination_date' => 'date',
            'commission_rate' => 'decimal:2',
            'base_salary' => 'decimal:2',
            'skills' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function dailyEntries(): HasMany
    {
        return $this->hasMany(DailyEntry::class);
    }

    public function advanceRequests(): HasMany
    {
        return $this->hasMany(AdvanceRequest::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'owner_id')->where('owner_type', 'employee');
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
