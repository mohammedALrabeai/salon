<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiToken extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'access_token_hash',
        'refresh_token_hash',
        'expires_at',
        'refresh_expires_at',
        'revoked_at',
        'last_used_at',
        'ip_address',
        'device_id',
        'device_name',
        'device_os',
        'device_version',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
