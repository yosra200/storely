<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use ApiResponse;

    public function privacyPolicy()
    {
        return $this->showSetting('privacy_policy');
    }

    public function termsAndConditions()
    {
        return $this->showSetting('terms_and_conditions');
    }

    public function update(Request $request)
    {
        if ($request->user()?->role !== 'admin') {
            return $this->errorResponse(__('messages.unauthorized'), 403);
        }

        $validated = $request->validate([
            'key' => ['required', 'string', 'in:privacy_policy,terms_and_conditions'],
            'value' => ['present', 'nullable', 'string'],
        ]);

        $setting = AppSetting::updateOrCreate(
            ['key' => $validated['key']],
            [
                'value' => $validated['value'],
                'type' => 'text',
                'is_sensitive' => false,
            ]
        );

        return $this->successResponse(
            ['key' => $setting->key, 'value' => $setting->value],
            __('messages.update_success')
        );
    }

    private function showSetting(string $key)
    {
        $setting = AppSetting::query()->where('key', $key)->first();

        return $this->successResponse(
            ['key' => $key, 'value' => $setting?->value],
            __('messages.success')
        );
    }
}