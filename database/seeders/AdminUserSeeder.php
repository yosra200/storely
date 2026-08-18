<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $login = env('ADMIN_EMAIL') ?: env('ADMIN_PHONE');
        $password = env('ADMIN_PASSWORD');

        if (! $login || ! $password) {
            throw new RuntimeException(
                'Set ADMIN_EMAIL or ADMIN_PHONE and ADMIN_PASSWORD in .env before running the seeder.'
            );
        }

        $attributes = [
            'name' => env('ADMIN_NAME', 'System Admin'),
            'password' => Hash::make($password),
            'role' => 'admin',
            'is_active' => true,
        ];

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $attributes['email'] = $login;
            $attributes['phone'] = null;
            $lookup = ['email' => $login];
        } else {
            $attributes['phone'] = $login;
            $attributes['email'] = null;
            $lookup = ['phone' => $login];
        }

        User::updateOrCreate($lookup, $attributes);


        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
