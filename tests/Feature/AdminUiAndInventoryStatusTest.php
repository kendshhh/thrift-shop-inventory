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
use Illuminate\Support\Str;
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

    public function test_archiving_item_keeps_it_visible_in_admin_inventory(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $item = Item::query()->create([
            'name' => 'Archive Visible Item',
            'slug' => 'archive-visible-item',
            'price' => 180,
            'quantity' => 2,
            'reserved_quantity' => 0,
            'description' => 'Should remain visible after archive.',
            'seller_name' => 'Archive Seller',
            'seller_contact_number' => '09170000011',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ACTIVE,
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.inventory.destroy', $item))
            ->assertRedirect(route('admin.inventory.index'));

        $item->refresh();

        $this->assertSame(ItemStatus::ARCHIVED, $item->status);
        $this->assertFalse($item->trashed());

        $this
            ->actingAs($admin)
            ->get(route('admin.inventory.index', ['status' => ItemStatus::ARCHIVED->value]))
            ->assertOk()
            ->assertSee('Archive Visible Item')
            ->assertSee('Archived');

        $this
            ->actingAs($admin)
            ->get(route('admin.inventory.show', $item))
            ->assertOk()
            ->assertSee('Archive Visible Item')
            ->assertSee('Archived');
    }

    public function test_previously_soft_deleted_archived_items_are_still_listed_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $item = Item::query()->create([
            'name' => 'Legacy Archived Item',
            'slug' => 'legacy-archived-item-'.Str::lower(Str::random(6)),
            'price' => 220,
            'quantity' => 1,
            'reserved_quantity' => 0,
            'description' => 'Legacy archived row.',
            'seller_name' => 'Legacy Seller',
            'seller_contact_number' => '09170000012',
            'condition' => ItemCondition::NEW,
            'status' => ItemStatus::ARCHIVED,
        ]);

        $item->delete();

        $this->assertTrue($item->fresh()->trashed());

        $this
            ->actingAs($admin)
            ->get(route('admin.inventory.index', ['status' => ItemStatus::ARCHIVED->value]))
            ->assertOk()
            ->assertSee('Legacy Archived Item')
            ->assertSee('Archived Record');

        $this
            ->actingAs($admin)
            ->get(route('admin.inventory.show', $item))
            ->assertOk()
            ->assertSee('Legacy Archived Item')
            ->assertSee('Recovered Archived Record');
    }

    public function test_admin_can_unarchive_item_from_inventory_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $item = Item::query()->create([
            'name' => 'Restore Me Item',
            'slug' => 'restore-me-item',
            'price' => 200,
            'quantity' => 4,
            'reserved_quantity' => 1,
            'description' => 'Should become active again.',
            'seller_name' => 'Restore Seller',
            'seller_contact_number' => '09170000013',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ARCHIVED,
        ]);

        $this
            ->actingAs($admin)
            ->from(route('admin.inventory.index', ['status' => ItemStatus::ARCHIVED->value]))
            ->patch(route('admin.inventory.unarchive', $item))
            ->assertRedirect(route('admin.inventory.index', ['status' => ItemStatus::ARCHIVED->value]));

        $item->refresh();

        $this->assertSame(ItemStatus::ACTIVE, $item->status);
        $this->assertFalse($item->trashed());

        $this
            ->actingAs($admin)
            ->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSee('Restore Me Item');
    }

    public function test_admin_can_unarchive_item_to_out_of_stock_when_no_available_quantity(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $item = Item::query()->create([
            'name' => 'Restore Empty Item',
            'slug' => 'restore-empty-item',
            'price' => 210,
            'quantity' => 2,
            'reserved_quantity' => 2,
            'description' => 'Should return as out of stock.',
            'seller_name' => 'Restore Seller',
            'seller_contact_number' => '09170000014',
            'condition' => ItemCondition::NEW,
            'status' => ItemStatus::ARCHIVED,
        ]);

        $this
            ->actingAs($admin)
            ->from(route('admin.inventory.show', $item))
            ->patch(route('admin.inventory.unarchive', $item))
            ->assertRedirect(route('admin.inventory.show', $item));

        $item->refresh();

        $this->assertSame(ItemStatus::OUT_OF_STOCK, $item->status);
        $this->assertFalse($item->trashed());

        $this
            ->actingAs($admin)
            ->get(route('admin.inventory.show', $item))
            ->assertOk()
            ->assertSee('Out of Stock');
    }
}
