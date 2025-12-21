<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, HasUuids, LogsModelActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'region',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'phone',
        'email',
        'manager_id',
        'status',
        'opening_time',
        'closing_time',
        'working_days',
        'settings',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'working_days' => 'array',
            'settings' => 'array',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function dailyEntries(): HasMany
    {
        return $this->hasMany(DailyEntry::class);
    }

    public function dayClosures(): HasMany
    {
        return $this->hasMany(DayClosure::class);
    }

    public function advanceRequests(): HasMany
    {
        return $this->hasMany(AdvanceRequest::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'owner_id')->where('owner_type', 'branch');
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
