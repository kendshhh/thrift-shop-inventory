<?php

namespace Tests\Feature;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\PickupSlot;
use App\Enums\ReservationStatus;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminUiAndInventoryStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_admin_users_page_hides_role_dropdown_and_still_updates_activation_state(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create([
            'is_active' => true,
            'suspended_at' => null,
        ]);
        $customer->assignRole('customer');

        $this
            ->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('id="user-role"', false)
            ->assertSee('name="is_active"', false);

        $this
            ->actingAs($admin)
            ->patch(route('admin.users.update', $customer), [
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.users.index'));

        $customer->refresh();

        $this->assertFalse($customer->is_active);
        $this->assertNotNull($customer->suspended_at);
        $this->assertTrue($customer->hasRole('customer'));
    }

    public function test_item_status_automatically_changes_with_available_quantity(): void
    {
        $item = Item::query()->create([
            'name' => 'Auto Status Item',
            'slug' => 'auto-status-item',
            'price' => 100,
            'quantity' => 0,
            'reserved_quantity' => 0,
            'description' => 'Testing automatic stock state updates.',
            'seller_name' => 'Stock Room',
            'seller_contact_number' => '09170000000',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ACTIVE,
        ]);

        $item->refresh();
        $this->assertSame(ItemStatus::OUT_OF_STOCK, $item->status);

        $item->quantity = 3;
        $item->save();
        $item->refresh();

        $this->assertSame(ItemStatus::ACTIVE, $item->status);

        $item->status = ItemStatus::ARCHIVED;
        $item->quantity = 6;
        $item->save();
        $item->refresh();

        $this->assertSame(ItemStatus::ARCHIVED, $item->status);
    }

    public function test_customer_notifications_render_shared_status_badges(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-BADGE-0001',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::AFTERNOON->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 220,
        ]);

        $customer->notify(new ReservationStatusUpdatedNotification(
            $reservation,
            ReservationStatus::PENDING->label(),
            PaymentStatus::PENDING->label()
        ));

        $this
            ->actingAs($customer)
            ->get(route('customer.notifications.index'))
            ->assertOk()
            ->assertSee('status-badge-unread', false)
            ->assertSee('status-badge-ready', false)
            ->assertSee('Reservation ready for pickup');
    }
}
