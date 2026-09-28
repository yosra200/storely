<?php

namespace Tests\Feature;

use App\Http\Requests\addCustomerRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SingletonRoleValidationTest extends TestCase
{
    public function test_supervisor_and_packing_roles_can_only_have_one_user(): void
    {
        DB::table('users')->insert([
            'name' => 'Existing Supervisor',
            'email' => 'supervisor@example.com',
            'phone' => '966500000001',
            'password' => bcrypt('password'),
            'role' => 'supervisor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $validator = Validator::make(['role' => 'supervisor'], (new addCustomerRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('role', $validator->errors()->toArray());

        DB::table('users')->insert([
            'name' => 'Existing Packer',
            'email' => 'packing@example.com',
            'phone' => '966500000002',
            'password' => bcrypt('password'),
            'role' => 'packing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packingValidator = Validator::make(['role' => 'packing'], (new addCustomerRequest)->rules());

        $this->assertTrue($packingValidator->fails());
        $this->assertArrayHasKey('role', $packingValidator->errors()->toArray());
    }

    public function test_customer_update_request_allows_password_change(): void
    {
        $validator = Validator::make([
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ], (new UpdateUserRequest)->rules());

        $this->assertFalse($validator->fails());
        $this->assertArrayNotHasKey('password', $validator->errors()->toArray());
    }

    public function test_admin_can_list_only_customer_role_users(): void
    {
        User::query()->create([
            'name' => 'Customer One',
            'email' => 'customer1@example.com',
            'phone' => '966500000010',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        User::query()->create([
            'name' => 'Customer Two',
            'email' => 'customer2@example.com',
            'phone' => '966500000011',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        User::query()->create([
            'name' => 'Sales User',
            'email' => 'sales@example.com',
            'phone' => '966500000012',
            'password' => bcrypt('password'),
            'role' => 'sales',
        ]);

        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '966500000013',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/customers')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $sales = User::query()->where('role', 'sales')->first();

        $this->actingAs($sales, 'sanctum')
            ->getJson('/api/customers')
            ->assertStatus(403);
    }
}
