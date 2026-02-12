<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

class Document extends Model
{
    use HasFactory, HasUuids, LogsModelActivity, SoftDeletes;

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

    public function userOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id')->where('owner_type', 'employee');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class);
    }

    protected function status(): Attribute
    {
        return Attribute::get(function (?string $value, array $attributes): ?string {
            $expiryDate = $attributes['expiry_date'] ?? null;

            if (!$expiryDate) {
                return 'safe';
            }

            $expiry = Carbon::parse($expiryDate)->startOfDay();
            $today = now()->startOfDay();

            if ($expiry->lt($today)) {
                return 'expired';
            }

            if ($expiry->lte($today->copy()->addDays(15))) {
                return 'urgent';
            }

            if ($expiry->lte($today->copy()->addDays(60))) {
                return 'near';
            }

            return 'safe';
        });
    }

    protected function daysRemaining(): Attribute
    {
        return Attribute::get(function (?int $value, array $attributes): ?int {
            $expiryDate = $attributes['expiry_date'] ?? null;

            if (!$expiryDate) {
                return null;
            }

            $expiry = Carbon::parse($expiryDate)->startOfDay();
            $today = now()->startOfDay();

            return $today->diffInDays($expiry, false);
        });
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
