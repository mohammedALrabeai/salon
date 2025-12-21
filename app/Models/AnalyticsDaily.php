<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDaily extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'analytics_daily';

    public const CREATED_AT = 'computed_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'date',
        'scope_type',
        'scope_id',
        'total_sales',
        'total_cash',
        'total_expense',
        'total_net',
        'total_commission',
        'total_bonus',
        'entries_count',
        'employees_count',
        'transactions_count',
        'avg_sale_value',
        'avg_commission_rate',
        'computed_at',
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
            'transactions_count' => 'integer',
            'avg_sale_value' => 'decimal:2',
            'avg_commission_rate' => 'decimal:2',
            'computed_at' => 'datetime',
        ];
    }
}
