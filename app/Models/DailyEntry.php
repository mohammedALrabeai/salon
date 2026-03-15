<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\LedgerEntry;

class DailyEntry extends Model
{
    use HasFactory, HasUuids, LogsModelActivity, SoftDeletes;

    public const PAYMENT_TYPE_CASH = 'cash';
    public const PAYMENT_TYPE_NETWORK = 'network';
    public const PAYMENT_TYPE_PURCHASES = 'purchases';

    public const PAYMENT_TYPES = [
        self::PAYMENT_TYPE_CASH,
        self::PAYMENT_TYPE_NETWORK,
        self::PAYMENT_TYPE_PURCHASES,
    ];

    protected $fillable = [
        'branch_id',
        'user_id',
        'date',
        'sales',
        'cash',
        'expense',
        'payment_type',
        'commission',
        'commission_rate',
        'bonus',
        'bonus_reason',
        'note',
        'transactions_count',
        'source',
        'is_locked',
        'locked_at',
        'locked_by',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'sales' => 'decimal:2',
            'cash' => 'decimal:2',
            'expense' => 'decimal:2',
            'payment_type' => 'string',
            'net' => 'decimal:2',
            'commission' => 'decimal:2',
            'bonus' => 'decimal:2',
            'transactions_count' => 'integer',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function booted(): void
    {
        static::created(function (DailyEntry $entry) {
            $amount = $entry->commission + $entry->bonus;
            if ($amount > 0) {
                LedgerEntry::create([
                    'party_type' => 'user',
                    'party_id' => $entry->user_id,
                    'date' => $entry->date,
                    'type' => 'credit',
                    'amount' => $amount,
                    'description' => 'عمولة اليوم (' . $entry->date->format('Y-m-d') . ')',
                    'source' => 'other',
                    'reference_id' => $entry->id,
                    'reference_type' => self::class,
                    'status' => 'confirmed',
                ]);
            }
        });

        static::updated(function (DailyEntry $entry) {
            $amount = $entry->commission + $entry->bonus;
            $ledgerEntry = LedgerEntry::where('reference_type', self::class)
                ->where('reference_id', $entry->id)
                ->first();

            if ($amount > 0) {
                if ($ledgerEntry) {
                    $ledgerEntry->update([
                        'amount' => $amount,
                        'date' => $entry->date,
                        'description' => 'عمولة اليوم (' . $entry->date->format('Y-m-d') . ')',
                    ]);
                } else {
                    LedgerEntry::create([
                        'party_type' => 'user',
                        'party_id' => $entry->user_id,
                        'date' => $entry->date,
                        'type' => 'credit',
                        'amount' => $amount,
                        'description' => 'عمولة اليوم (' . $entry->date->format('Y-m-d') . ')',
                        'source' => 'other',
                        'reference_id' => $entry->id,
                        'reference_type' => self::class,
                        'status' => 'confirmed',
                    ]);
                }
            } elseif ($ledgerEntry) {
                $ledgerEntry->delete();
            }
        });

        static::deleted(function (DailyEntry $entry) {
            LedgerEntry::where('reference_type', self::class)
                ->where('reference_id', $entry->id)
                ->delete();
        });
    }
}
