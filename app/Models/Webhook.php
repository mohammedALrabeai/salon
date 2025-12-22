<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'url',
        'events',
        'secret',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
        ];
    }
}
