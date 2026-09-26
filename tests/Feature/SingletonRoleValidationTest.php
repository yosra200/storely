<?php

namespace Tests\Feature;

use App\Http\Requests\addCustomerRequest;
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
}
