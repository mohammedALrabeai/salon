<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ApiTokenService
{
    /**
     * @return array{token: ApiToken, access_token: string, refresh_token: string, expires_in: int, expires_at: string}
     */
    public function create(User $user, array $deviceInfo = [], ?string $ipAddress = null): array
    {
        $accessToken = Str::random(64);
        $refreshToken = Str::random(80);

        $expiresIn = (int) config('api.access_token_ttl', 3600);
        $expiresAt = now()->addSeconds($expiresIn);
        $refreshExpiresAt = now()->addDays((int) config('api.refresh_token_ttl_days', 30));

        $token = ApiToken::create([
            'user_id' => $user->id,
            'access_token_hash' => hash('sha256', $accessToken),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'expires_at' => $expiresAt,
            'refresh_expires_at' => $refreshExpiresAt,
            'ip_address' => $ipAddress,
            'device_id' => Arr::get($deviceInfo, 'device_id'),
            'device_name' => Arr::get($deviceInfo, 'device_name'),
            'device_os' => Arr::get($deviceInfo, 'os'),
            'device_version' => Arr::get($deviceInfo, 'app_version'),
        ]);

        return [
            'token' => $token,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $expiresIn,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * @return array{token: ApiToken, access_token: string, refresh_token: string, expires_in: int, expires_at: string}
     */
    public function rotate(ApiToken $token): array
    {
        $token->forceFill(['revoked_at' => now()])->save();

        return $this->create($token->user, [
            'device_id' => $token->device_id,
            'device_name' => $token->device_name,
            'os' => $token->device_os,
            'app_version' => $token->device_version,
        ], $token->ip_address);
    }

    public function revoke(ApiToken $token): void
    {
        $token->forceFill(['revoked_at' => now()])->save();
    }

    public function isRefreshExpired(ApiToken $token): bool
    {
        return $token->refresh_expires_at instanceof Carbon && $token->refresh_expires_at->isPast();
    }
}
