<?php

namespace Tests\Feature;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GuestReserveNowRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('customer', 'web');
        Role::findOrCreate('admin', 'web');
    }

    public function test_guest_reserve_now_redirects_to_customer_login_and_returns_to_item_after_authentication(): void
    {
        $item = Item::query()->create([
            'name' => 'Reserve Now Test Item',
            'slug' => 'reserve-now-test-item',
            'price' => 299,
            'quantity' => 2,
            'reserved_quantity' => 0,
            'description' => 'Item for reserve now redirect test.',
            'seller_name' => 'Shop',
            'seller_contact_number' => '09170000000',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ACTIVE,
        ]);

        $user = User::factory()->create();
        $user->assignRole('customer');

        $this
            ->get(route('items.reserve-now', $item))
            ->assertRedirect(route('login', ['role' => 'customer']))
            ->assertSessionHas('url.intended', route('items.show', $item).'?reserve=1');

        $this
            ->post('/login?role=customer', [
                'role' => 'customer',
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('items.show', $item).'?reserve=1');
    }
}
