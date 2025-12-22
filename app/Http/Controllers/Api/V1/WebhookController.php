<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Webhook;
use Illuminate\Http\Request;

class WebhookController extends ApiController
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'url' => ['required', 'url'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string'],
            'secret' => ['nullable', 'string'],
        ]);

        $webhook = Webhook::create([
            'url' => $data['url'],
            'events' => $data['events'],
            'secret' => $data['secret'] ?? null,
            'status' => 'active',
        ]);

        return $this->success([
            'id' => $webhook->id,
            'url' => $webhook->url,
            'events' => $webhook->events,
            'status' => $webhook->status,
            'created_at' => $webhook->created_at?->toIso8601String(),
        ], null, 201);
    }
}
