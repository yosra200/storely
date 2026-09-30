<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        AppSetting::firstOrCreate(
            ['key' => 'privacy_policy'],
            [
                'value' => 'سياسة الخصوصية',
                'type' => 'text',
                'is_sensitive' => false,
            ]
        );

        AppSetting::firstOrCreate(
            ['key' => 'terms_and_conditions'],
            [
                'value' => 'الشروط والأحكام',
                'type' => 'text',
                'is_sensitive' => false,
            ]
        );
    }
}
