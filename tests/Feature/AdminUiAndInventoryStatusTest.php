<?php

namespace Tests\Feature;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\PickupSlot;
use App\Enums\ReservationStatus;
use App\Models\Category;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\ReservationItem;
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
                'confirmation_text' => 'confirm',
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

    public function test_admin_inventory_update_rejects_non_numeric_seller_contact_number(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $item = Item::query()->create([
            'name' => 'Contact Rule Item',
            'slug' => 'contact-rule-item',
            'price' => 150,
            'quantity' => 2,
            'reserved_quantity' => 0,
            'description' => 'Validates contact number input.',
            'seller_name' => 'Contact Seller',
            'seller_contact_number' => '09170000016',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ACTIVE,
        ]);

        $this
            ->actingAs($admin)
            ->from(route('admin.inventory.edit', $item))
            ->put(route('admin.inventory.update', $item), [
                'name' => 'Contact Rule Item',
                'price' => 150,
                'quantity' => 2,
                'description' => 'Validates contact number input.',
                'seller_name' => 'Contact Seller',
                'seller_contact_number' => '0917ABC1234',
                'condition' => ItemCondition::GENTLY_USED->value,
                'status' => ItemStatus::ACTIVE->value,
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('admin.inventory.edit', $item))
            ->assertSessionHasErrors([
                'seller_contact_number' => 'Contact number must contain numbers only.',
            ]);

        $item->refresh();

        $this->assertSame('09170000016', $item->seller_contact_number);
    }

    public function test_admin_inventory_rejects_numbers_in_seller_name(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this
            ->actingAs($admin)
            ->from(route('admin.inventory.create'))
            ->post(route('admin.inventory.store'), [
                'name' => 'Seller Name Rule Item',
                'price' => 150,
                'quantity' => 2,
                'description' => 'Validates seller name input.',
                'seller_name' => 'Seller 123',
                'seller_contact_number' => '09170000016',
                'condition' => ItemCondition::GENTLY_USED->value,
                'status' => ItemStatus::ACTIVE->value,
            ])
            ->assertRedirect(route('admin.inventory.create'))
            ->assertSessionHasErrors(['seller_name']);
    }

    public function test_admin_inventory_create_rejects_negative_price_and_quantity_with_clear_messages(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this
            ->actingAs($admin)
            ->from(route('admin.inventory.create'))
            ->post(route('admin.inventory.store'), [
                'name' => 'Invalid Number Item',
                'price' => -50,
                'quantity' => -1,
                'description' => 'Testing negative validation.',
                'seller_name' => 'Test Seller',
                'seller_contact_number' => '09170000021',
                'condition' => ItemCondition::GENTLY_USED->value,
                'status' => ItemStatus::ACTIVE->value,
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('admin.inventory.create'))
            ->assertSessionHasErrors([
                'price' => 'Price cannot be negative. Enter 0 or a higher amount.',
                'quantity' => 'Quantity cannot be negative. Enter 0 or a higher value.',
            ]);
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
            ->delete(route('admin.inventory.destroy', $item), [
                'confirmation_text' => 'confirm',
            ])
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
            ->patch(route('admin.inventory.unarchive', $item), [
                'confirmation_text' => 'confirm',
            ])
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
            ->patch(route('admin.inventory.unarchive', $item), [
                'confirmation_text' => 'confirm',
            ])
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

    public function test_admin_can_permanently_delete_inventory_item_without_reservation_links(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $item = Item::query()->create([
            'name' => 'Permanent Delete Item',
            'slug' => 'permanent-delete-item',
            'price' => 260,
            'quantity' => 1,
            'reserved_quantity' => 0,
            'description' => 'Can be permanently deleted.',
            'seller_name' => 'Delete Seller',
            'seller_contact_number' => '09170000017',
            'condition' => ItemCondition::NEW,
            'status' => ItemStatus::ARCHIVED,
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.inventory.force-destroy', $item), [
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('admin.inventory.index'));

        $this->assertDatabaseMissing('items', [
            'id' => $item->id,
        ]);
    }

    public function test_admin_cannot_permanently_delete_inventory_item_linked_to_reservations(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $item = Item::query()->create([
            'name' => 'Protected Delete Item',
            'slug' => 'protected-delete-item',
            'price' => 275,
            'quantity' => 2,
            'reserved_quantity' => 1,
            'description' => 'Linked to an existing reservation.',
            'seller_name' => 'Protected Seller',
            'seller_contact_number' => '09170000018',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ARCHIVED,
        ]);

        $reservation = Reservation::query()->create([
            'reference' => 'RSV-DELETE-0001',
            'user_id' => null,
            'status' => ReservationStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 275,
        ]);

        ReservationItem::query()->create([
            'reservation_id' => $reservation->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'unit_price' => 275,
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.inventory.force-destroy', $item), [
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('admin.inventory.show', $item))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
        ]);
    }

    public function test_admin_can_permanently_delete_inventory_item_when_only_historical_reservations_exist(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $item = Item::query()->create([
            'name' => 'Historical Delete Item',
            'slug' => 'historical-delete-item',
            'price' => 300,
            'quantity' => 2,
            'reserved_quantity' => 0,
            'description' => 'Only completed reservation history exists.',
            'seller_name' => 'History Seller',
            'seller_contact_number' => '09170000020',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ARCHIVED,
        ]);

        $reservation = Reservation::query()->create([
            'reference' => 'RSV-DELETE-0002',
            'user_id' => null,
            'status' => ReservationStatus::COMPLETED,
            'payment_status' => PaymentStatus::COMPLETED,
            'pickup_date' => now()->subDay()->toDateString(),
            'pickup_slot' => PickupSlot::AFTERNOON->value,
            'expires_at' => now()->subDays(2),
            'paid_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
            'total_amount' => 300,
        ]);

        ReservationItem::query()->create([
            'reservation_id' => $reservation->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'unit_price' => 300,
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.inventory.force-destroy', $item), [
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('admin.inventory.index'));

        $this->assertDatabaseMissing('items', [
            'id' => $item->id,
        ]);
    }

    public function test_archiving_category_detaches_linked_items(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $category = Category::query()->create([
            'name' => 'Archive Category',
            'slug' => 'archive-category',
            'description' => 'Will be archived.',
            'is_active' => true,
        ]);

        $item = Item::query()->create([
            'category_id' => $category->id,
            'name' => 'Category Linked Item',
            'slug' => 'category-linked-item',
            'price' => 190,
            'quantity' => 3,
            'reserved_quantity' => 0,
            'description' => 'Should remain accessible after category archive.',
            'seller_name' => 'Category Seller',
            'seller_contact_number' => '09170000019',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ACTIVE,
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category), [
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);

        $item->refresh();

        $this->assertNull($item->category_id);

        $this
            ->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Uncategorized');
    }

    public function test_admin_can_rename_item_without_changing_its_slug_or_breaking_public_item_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $item = Item::query()->create([
            'name' => 'Original Name',
            'slug' => 'original-name',
            'price' => 320,
            'quantity' => 5,
            'reserved_quantity' => 0,
            'description' => 'Rename me safely.',
            'seller_name' => 'Rename Seller',
            'seller_contact_number' => '09170000015',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ACTIVE,
        ]);

        $oldSlug = $item->slug;

        $this
            ->actingAs($admin)
            ->put(route('admin.inventory.update', $item), [
                'name' => 'Renamed Item',
                'price' => 320,
                'quantity' => 5,
                'description' => 'Rename me safely.',
                'seller_name' => 'Rename Seller',
                'seller_contact_number' => '09170000015',
                'condition' => ItemCondition::GENTLY_USED->value,
                'status' => ItemStatus::ACTIVE->value,
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('admin.inventory.show', $item));

        $item->refresh();

        $this->assertSame($oldSlug, $item->slug);
        $this->assertSame('Renamed Item', $item->name);

        $this
            ->get(route('items.show', ['item' => $oldSlug]))
            ->assertOk()
            ->assertSee('Renamed Item');

        $this
            ->actingAs($admin)
            ->get(route('admin.inventory.show', ['item' => $oldSlug]))
            ->assertOk()
            ->assertSee('Renamed Item');
    }
}
