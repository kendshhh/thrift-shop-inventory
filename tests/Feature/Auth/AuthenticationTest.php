<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RolesAndAdminSeeder;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect(route('auth.role-selection', ['intent' => 'login']));
    }

    public function test_login_screen_can_be_rendered_after_role_selection(): void
    {
        $response = $this->get('/login?role=customer');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $this->seed(RolesAndAdminSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->post('/login', [
            'role' => 'customer',
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_newly_registered_customer_can_log_out_and_log_back_in_with_the_same_credentials(): void
    {
        $this->post('/register', [
            'name' => 'Fresh Customer',
            'email' => 'freshcustomer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'legal_consent' => '1',
        ]);

        $user = User::query()->where('email', 'freshcustomer@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('customer'));

        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $response = $this->post('/login', [
            'role' => 'customer',
            'email' => 'FreshCustomer@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $this->seed(RolesAndAdminSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('customer');

        $this->post('/login', [
            'role' => 'customer',
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_the_wrong_role_selection(): void
    {
        $this->seed(RolesAndAdminSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->post('/login', [
            'role' => 'customer',
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'email' => 'You must be an admin in order to sign in with these credentials.',
        ]);
    }

    public function test_default_admin_account_is_restored_when_admin_sign_in_is_requested(): void
    {
        $this->seed(RolesAndAdminSeeder::class);

        $admin = User::query()->where('email', 'admin@thriftshop.local')->firstOrFail();
        $admin->delete();

        $response = $this->post('/login', [
            'role' => 'admin',
            'email' => 'admin@thriftshop.local',
            'password' => '123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $this->assertNotNull(User::query()->where('email', 'admin@thriftshop.local')->first());
    }

    public function test_default_admin_password_is_restored_when_admin_sign_in_is_requested(): void
    {
        $this->seed(RolesAndAdminSeeder::class);

        $admin = User::query()->where('email', 'admin@thriftshop.local')->firstOrFail();
        $admin->forceFill([
            'password' => Hash::make('wrong-password'),
        ])->save();

        $response = $this->post('/login', [
            'role' => 'admin',
            'email' => 'admin@thriftshop.local',
            'password' => '123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
