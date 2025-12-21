<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayClosure extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'branch_id',
        'date',
        'total_sales',
        'total_cash',
        'total_expense',
        'total_net',
        'total_commission',
        'total_bonus',
        'entries_count',
        'employees_count',
        'closed_by',
        'closed_at',
        'pdf_url',
        'pdf_generated_at',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_sales' => 'decimal:2',
            'total_cash' => 'decimal:2',
            'total_expense' => 'decimal:2',
            'total_net' => 'decimal:2',
            'total_commission' => 'decimal:2',
            'total_bonus' => 'decimal:2',
            'entries_count' => 'integer',
            'employees_count' => 'integer',
            'closed_at' => 'datetime',
            'pdf_generated_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
