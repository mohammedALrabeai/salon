<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiTokenAuth
{
    /**
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $rawToken = $this->extractBearerToken($request->header('Authorization'));

        if (! $rawToken) {
            return ApiResponse::error('TOKEN_INVALID', 'الرمز غير صالح', 401);
        }

        $tokenHash = hash('sha256', $rawToken);
        $token = ApiToken::query()->where('access_token_hash', $tokenHash)->first();

        if (! $token) {
            return ApiResponse::error('TOKEN_INVALID', 'الرمز غير صالح', 401);
        }

        if ($token->revoked_at) {
            return ApiResponse::error('TOKEN_INVALID', 'الرمز غير صالح', 401);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return ApiResponse::error('TOKEN_EXPIRED', 'انتهت صلاحية الرمز', 401);
        }

        $user = $token->user;

        if (! $user || $user->status !== 'active') {
            return ApiResponse::error('ACCOUNT_INACTIVE', 'الحساب غير نشط', 403, [
                'status' => $user?->status,
            ]);
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            return ApiResponse::error('ACCOUNT_LOCKED', 'الحساب مقفل مؤقتاً', 403, [
                'locked_until' => $user->locked_until->toIso8601String(),
            ]);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('api_token', $token);

        $token->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }

    private function extractBearerToken(?string $header): ?string
    {
        if (! $header) {
            return null;
        }

        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return trim(substr($header, 7)) ?: null;
    }
}
