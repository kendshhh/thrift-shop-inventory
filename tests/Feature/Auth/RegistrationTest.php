<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_role_based_registration_urls_redirect_to_the_customer_registration_screen(): void
    {
        $response = $this->get('/register?role=customer');

        $response->assertRedirect(route('register'));

        $response = $this->get('/register?role=admin');

        $response->assertRedirect(route('register'));
    }

    public function test_register_intent_redirects_to_the_customer_registration_screen(): void
    {
        $response = $this->get(route('auth.role-selection', ['intent' => 'register']));

        $response->assertRedirect(route('register'));
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'Test@Example.COM',
            'password' => 'password',
            'password_confirmation' => 'password',
            'legal_consent' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('customer'));
        $this->assertNotNull($user->privacy_notice_accepted_at);
        $this->assertNotNull($user->terms_accepted_at);
    }

    public function test_registration_requires_legal_consent(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['legal_consent']);

        $this->assertGuest();
    }
}
