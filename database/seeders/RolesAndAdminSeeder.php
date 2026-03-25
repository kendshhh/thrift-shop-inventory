<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage inventory',
            'manage categories',
            'manage reservations',
            'manage users',
            'update stock',
            'archive inventory',
            'browse inventory',
            'reserve items',
            'view own reservations',
            'manage own profile',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);
        $customerRole = Role::query()->firstOrCreate(['name' => 'customer']);

        $adminRole->syncPermissions([
            'manage inventory',
            'manage categories',
            'manage reservations',
            'manage users',
            'update stock',
            'archive inventory',
        ]);

        $customerRole->syncPermissions([
            'browse inventory',
            'reserve items',
            'view own reservations',
            'manage own profile',
        ]);

        $adminPassword = env('DEFAULT_ADMIN_PASSWORD', '123');
        $customerPassword = env('DEFAULT_CUSTOMER_PASSWORD', '123');

        $admin = User::query()->firstOrCreate(
            ['email' => env('DEFAULT_ADMIN_EMAIL', 'admin@thriftshop.local')],
            [
                'name' => env('DEFAULT_ADMIN_NAME', 'System Admin'),
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $admin->forceFill([
            'name' => env('DEFAULT_ADMIN_NAME', 'System Admin'),
            'password' => Hash::make($adminPassword),
            'email_verified_at' => $admin->email_verified_at ?? now(),
            'is_active' => true,
        ])->save();

        $admin->syncRoles([$adminRole]);

        $customer = User::query()->firstOrCreate(
            ['email' => env('DEFAULT_CUSTOMER_EMAIL', 'customer@thriftshop.local')],
            [
                'name' => env('DEFAULT_CUSTOMER_NAME', 'Default Customer'),
                'password' => Hash::make($customerPassword),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $customer->forceFill([
            'name' => env('DEFAULT_CUSTOMER_NAME', 'Default Customer'),
            'password' => Hash::make($customerPassword),
            'email_verified_at' => $customer->email_verified_at ?? now(),
            'is_active' => true,
        ])->save();

        $customer->syncRoles([$customerRole]);
    }
}
