<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolesAndAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_seeder_creates_missing_default_accounts_without_deleting_existing_data(): void
    {
        $this->seed(RolesAndAdminSeeder::class);

        $admin = User::query()->where('email', 'admin@thriftshop.local')->first();
        $customer = User::query()->where('email', 'customer@thriftshop.local')->first();

        $this->assertNotNull($admin);
        $this->assertNotNull($customer);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($customer->hasRole('customer'));
    }

    public function test_seeder_does_not_overwrite_existing_default_account_information(): void
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');

        $admin = User::factory()->create([
            'email' => 'admin@thriftshop.local',
            'name' => 'Existing Admin Name',
            'password' => Hash::make('keep-this-password'),
            'is_active' => false,
            'email_verified_at' => null,
        ]);

        $customer = User::factory()->create([
            'email' => 'customer@thriftshop.local',
            'name' => 'Existing Customer Name',
            'password' => Hash::make('customer-secret'),
            'is_active' => false,
            'email_verified_at' => null,
        ]);

        $this->seed(RolesAndAdminSeeder::class);

        $admin->refresh();
        $customer->refresh();

        $this->assertSame('Existing Admin Name', $admin->name);
        $this->assertSame('Existing Customer Name', $customer->name);
        $this->assertFalse($admin->is_active);
        $this->assertFalse($customer->is_active);
        $this->assertNull($admin->email_verified_at);
        $this->assertNull($customer->email_verified_at);
        $this->assertTrue(Hash::check('keep-this-password', $admin->password));
        $this->assertTrue(Hash::check('customer-secret', $customer->password));
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($customer->hasRole('customer'));
    }
}