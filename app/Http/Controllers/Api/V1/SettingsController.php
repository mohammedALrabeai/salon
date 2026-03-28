<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends ApiController
{
    public function profile(Request $request)
    {
        return $this->success($this->profilePayload($request->user()));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar_url' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string'],
            'business_name' => ['nullable', 'string', 'max:150'],
        ]);

        $profileSettings = $this->profileSettings($user->settings ?? []);
        $profileSettings = $this->mergeOptionalKeys($profileSettings, $data, [
            'business_name',
        ]);

        $settings = $user->settings ?? [];
        $settings['profile'] = $profileSettings;

        $user->forceFill([
            'name' => $data['name'] ?? $user->name,
            'phone' => $data['phone'] ?? $user->phone,
            'email' => array_key_exists('email', $data) ? $data['email'] : $user->email,
            'avatar_url' => array_key_exists('avatar_url', $data) ? $data['avatar_url'] : $user->avatar_url,
            'bio' => array_key_exists('bio', $data) ? $data['bio'] : $user->bio,
            'settings' => $settings,
            'updated_by' => $user->id,
        ])->save();

        return $this->success($this->profilePayload($user->fresh('branch')), 'تم حفظ بيانات الملف الشخصي بنجاح');
    }

    public function preferences(Request $request)
    {
        return $this->success($this->preferencesPayload($request->user()));
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'notifications' => ['nullable', 'array'],
            'notifications.email_notifications' => ['nullable', 'boolean'],
            'notifications.sms_notifications' => ['nullable', 'boolean'],
            'notifications.daily_reports' => ['nullable', 'boolean'],
            'notifications.advance_requests' => ['nullable', 'boolean'],
            'notifications.document_expiry' => ['nullable', 'boolean'],
            'notifications.low_balance' => ['nullable', 'boolean'],
            'system' => ['nullable', 'array'],
            'system.language' => ['nullable', 'string', Rule::in(['ar', 'en'])],
            'system.timezone' => ['nullable', 'string', 'max:100'],
            'system.currency' => ['nullable', 'string', Rule::in(['SAR', 'AED', 'KWD', 'USD'])],
            'system.date_format' => ['nullable', 'string', Rule::in(['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'])],
        ]);

        $settings = $user->settings ?? [];
        $preferences = $user->preferences ?? [];

        if (isset($data['notifications'])) {
            $preferences['notifications'] = $this->mergeOptionalKeys(
                $this->notificationSettings($preferences),
                $data['notifications'],
                [
                    'email_notifications',
                    'sms_notifications',
                    'daily_reports',
                    'advance_requests',
                    'document_expiry',
                    'low_balance',
                ]
            );
        }

        if (isset($data['system'])) {
            $settings['system'] = $this->mergeOptionalKeys(
                $this->systemSettings($settings),
                $data['system'],
                ['language', 'timezone', 'currency', 'date_format']
            );
        }

        $user->forceFill([
            'settings' => $settings,
            'preferences' => $preferences,
            'updated_by' => $user->id,
        ])->save();

        return $this->success($this->preferencesPayload($user->fresh()), 'تم حفظ الإعدادات بنجاح');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->password_hash)) {
            return $this->error('INVALID_CREDENTIALS', 'كلمة المرور الحالية غير صحيحة', 401);
        }

        $user->forceFill([
            'password_hash' => Hash::make($data['new_password']),
            'updated_by' => $user->id,
        ])->save();

        return $this->success(null, 'تم تغيير كلمة المرور بنجاح');
    }

    private function profilePayload($user): array
    {
        $settings = $user->settings ?? [];
        $profileSettings = $this->profileSettings($settings);

        return [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'business_name' => $profileSettings['business_name'],
            'avatar_url' => $user->avatar_url,
            'bio' => $user->bio,
            'role' => $user->role,
            'branch' => $user->branch ? [
                'id' => $user->branch->id,
                'name' => $user->branch->name,
                'code' => $user->branch->code,
            ] : null,
        ];
    }

    private function preferencesPayload($user): array
    {
        $settings = $user->settings ?? [];
        $preferences = $user->preferences ?? [];

        return [
            'notifications' => $this->notificationSettings($preferences),
            'system' => $this->systemSettings($settings),
        ];
    }

    private function profileSettings(array $settings): array
    {
        $profile = is_array($settings['profile'] ?? null) ? $settings['profile'] : [];

        return [
            'business_name' => $profile['business_name'] ?? '',
        ];
    }

    private function notificationSettings(array $preferences): array
    {
        $notifications = is_array($preferences['notifications'] ?? null) ? $preferences['notifications'] : [];

        return [
            'email_notifications' => (bool) ($notifications['email_notifications'] ?? true),
            'sms_notifications' => (bool) ($notifications['sms_notifications'] ?? false),
            'daily_reports' => (bool) ($notifications['daily_reports'] ?? true),
            'advance_requests' => (bool) ($notifications['advance_requests'] ?? true),
            'document_expiry' => (bool) ($notifications['document_expiry'] ?? true),
            'low_balance' => (bool) ($notifications['low_balance'] ?? false),
        ];
    }

    private function systemSettings(array $settings): array
    {
        $system = is_array($settings['system'] ?? null) ? $settings['system'] : [];

        return [
            'language' => $system['language'] ?? 'ar',
            'timezone' => $system['timezone'] ?? 'Asia/Riyadh',
            'currency' => $system['currency'] ?? 'SAR',
            'date_format' => $system['date_format'] ?? 'DD/MM/YYYY',
        ];
    }

    private function mergeOptionalKeys(array $target, array $source, array $keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $source)) {
                $target[$key] = $source[$key];
            }
        }

        return $target;
    }
}
