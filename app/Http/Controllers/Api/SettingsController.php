<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use ApiResponse;

    private const PUBLIC_SETTING_KEYS = [
        'privacy_policy',
        'terms_and_conditions',
    ];

    public function index()
    {
        return $this->successResponse(
            $this->publicSettings(),
            __('messages.success')
        );
    }

    public function update(Request $request)
    {
        if ($request->user()?->role !== 'admin') {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $validated = $request->validate([
            'settings' => ['required', 'array:privacy_policy,terms_and_conditions', 'min:1'],
            'settings.privacy_policy' => ['sometimes', 'nullable', 'string'],
            'settings.terms_and_conditions' => ['sometimes', 'nullable', 'string'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            AppSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => 'text',
                    'is_sensitive' => false,
                ]
            );
        }

        return $this->successResponse(
            $this->publicSettings(),
            __('messages.update_success')
        );
    }

    private function publicSettings(): array
    {
        $settings = AppSetting::query()
            ->whereIn('key', self::PUBLIC_SETTING_KEYS)
            ->pluck('value', 'key');

        return collect(self::PUBLIC_SETTING_KEYS)
            ->mapWithKeys(fn (string $key) => [$key => $settings->get($key)])
            ->all();
    }
}