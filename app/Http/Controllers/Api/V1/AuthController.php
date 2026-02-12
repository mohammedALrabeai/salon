<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ApiToken;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function __construct(private readonly ApiTokenService $tokens)
    {
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_info.device_id' => ['nullable', 'string', 'max:100'],
            'device_info.device_name' => ['nullable', 'string', 'max:100'],
            'device_info.os' => ['nullable', 'string', 'max:50'],
            'device_info.app_version' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::query()->where('phone', $data['phone'])->first();

        if (! $user) {
            return $this->error('INVALID_CREDENTIALS', 'رقم الجوال أو كلمة المرور غير صحيحة', 401);
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            return $this->error('ACCOUNT_LOCKED', 'الحساب مقفل مؤقتاً', 403, [
                'locked_until' => $user->locked_until->toIso8601String(),
            ]);
        }

        if ($user->status !== 'active') {
            return $this->error('ACCOUNT_INACTIVE', 'حسابك غير نشط. يرجى التواصل مع الإدارة', 403, [
                'status' => $user->status,
            ]);
        }

        if (! Hash::check($data['password'], $user->password_hash)) {
            $this->handleFailedLogin($user);

            return $this->error('INVALID_CREDENTIALS', 'رقم الجوال أو كلمة المرور غير صحيحة', 401);
        }

        $user->forceFill([
            'failed_login_count' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $tokenPayload = $this->tokens->create($user, $data['device_info'] ?? [], $request->ip());

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'branch_id' => $user->branch_id,
                'branch_name' => $user->branch?->name,
                'avatar_url' => $user->avatar_url,
                'status' => $user->status,
                'national_id' => $user->national_id,
                'passport_number' => $user->passport_number,
                'hire_date' => $user->hire_date?->toDateString(),
                'termination_date' => $user->termination_date?->toDateString(),
                'employment_type' => $user->employment_type,
                'commission_rate' => $user->commission_rate !== null ? (float) $user->commission_rate : null,
                'commission_type' => $user->commission_type,
                'base_salary' => $user->base_salary !== null ? (float) $user->base_salary : null,
                'skills' => $user->skills ?? [],
                'settings' => $user->settings ?? [],
            ],
            'tokens' => [
                'access_token' => $tokenPayload['access_token'],
                'refresh_token' => $tokenPayload['refresh_token'],
                'token_type' => 'Bearer',
                'expires_in' => $tokenPayload['expires_in'],
                'expires_at' => $tokenPayload['expires_at'],
            ],
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ], 'تم تسجيل الدخول بنجاح');
    }

    public function refresh(Request $request)
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $token = ApiToken::query()
            ->where('refresh_token_hash', hash('sha256', $data['refresh_token']))
            ->first();

        if (! $token || $token->revoked_at) {
            return $this->error('TOKEN_INVALID', 'الرمز غير صالح', 401);
        }

        if ($token->refresh_expires_at && $token->refresh_expires_at->isPast()) {
            return $this->error('TOKEN_EXPIRED', 'انتهت صلاحية الرمز', 401);
        }

        if (! $token->user || $token->user->status !== 'active') {
            return $this->error('ACCOUNT_INACTIVE', 'حسابك غير نشط. يرجى التواصل مع الإدارة', 403, [
                'status' => $token->user?->status,
            ]);
        }

        $payload = $this->tokens->rotate($token);

        return $this->success([
            'access_token' => $payload['access_token'],
            'refresh_token' => $payload['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $payload['expires_in'],
            'expires_at' => $payload['expires_at'],
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->attributes->get('api_token');

        if ($token instanceof ApiToken) {
            $this->tokens->revoke($token);
        }

        return $this->success(null, 'تم تسجيل الخروج بنجاح');
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'branch' => $user->branch ? [
                'id' => $user->branch->id,
                'name' => $user->branch->name,
                'code' => $user->branch->code,
            ] : null,
            'avatar_url' => $user->avatar_url,
            'status' => $user->status,
            'national_id' => $user->national_id,
            'passport_number' => $user->passport_number,
            'hire_date' => $user->hire_date?->toDateString(),
            'termination_date' => $user->termination_date?->toDateString(),
            'employment_type' => $user->employment_type,
            'commission_rate' => $user->commission_rate !== null ? (float) $user->commission_rate : null,
            'commission_type' => $user->commission_type,
            'base_salary' => $user->base_salary !== null ? (float) $user->base_salary : null,
            'skills' => $user->skills ?? [],
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
        ]);
    }

    private function handleFailedLogin(User $user): void
    {
        $maxAttempts = (int) config('api.lockout.max_attempts', 5);
        $lockMinutes = (int) config('api.lockout.decay_minutes', 15);

        $user->increment('failed_login_count');

        if ($user->failed_login_count >= $maxAttempts) {
            $user->forceFill([
                'locked_until' => now()->addMinutes($lockMinutes),
            ])->save();
        }
    }
}
