<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids, LogsModelActivity;

    public const UPDATED_AT = null;

    protected $fillable = [
        'type',
        'target_type',
        'target_id',
        'title',
        'message',
        'data',
        'action_url',
        'status',
        'priority',
        'channels',
        'created_at',
        'sent_at',
        'read_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'channels' => 'array',
            'created_at' => 'datetime',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
