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
use App\Models\User;
use App\Notifications\AdminReservationNotification;
use App\Notifications\LowStockNotification;
use App\Notifications\ReservationStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PageSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_admin_users_page_filters_by_role_state_and_search(): void
    {
        $admin = User::factory()->create(['name' => 'Main Admin']);
        $admin->assignRole('admin');

        $matchingCustomer = User::factory()->create([
            'name' => 'Filter Match',
            'email' => 'match@example.com',
            'is_active' => false,
        ]);
        $matchingCustomer->assignRole('customer');

        $otherAdmin = User::factory()->create([
            'name' => 'Other Admin',
            'email' => 'other-admin@example.com',
            'is_active' => true,
        ]);
        $otherAdmin->assignRole('admin');

        $this
            ->actingAs($admin)
            ->get(route('admin.users.index', [
                'search' => 'Filter Match',
                'role' => 'customer',
                'state' => 'suspended',
            ]))
            ->assertOk()
            ->assertSee('Filter Match')
            ->assertDontSee('Other Admin');
    }

    public function test_admin_notifications_page_filters_by_search_state_and_event_type(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create(['name' => 'Alert Customer']);
        $customer->assignRole('customer');

        $item = Item::query()->create([
            'name' => 'Low Filter Jacket',
            'slug' => 'low-filter-jacket',
            'price' => 450,
            'quantity' => 1,
            'reserved_quantity' => 0,
            'description' => 'Low stock target item.',
            'seller_name' => 'Stock Keeper',
            'seller_contact_number' => '09170000000',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ACTIVE,
        ]);

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-FILTER-0001',
            'status' => ReservationStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 450,
        ]);

        $admin->notify(new LowStockNotification($item));
        $admin->notify(new AdminReservationNotification($reservation, 'new_reservation'));

        $readNotification = $admin->notifications()->where('data->reference', 'RSV-FILTER-0001')->firstOrFail();
        $readNotification->markAsRead();

        $this
            ->actingAs($admin)
            ->get(route('admin.notifications.index', [
                'search' => 'Low Filter Jacket',
                'read_state' => 'unread',
                'event_type' => 'low_stock',
            ]))
            ->assertOk()
            ->assertSee('Low Stock Alert')
            ->assertSee('Low Filter Jacket')
            ->assertDontSee('New reservation received');
    }

    public function test_admin_inventory_page_sorts_items_by_price(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Item::query()->create([
            'name' => 'Budget Tee',
            'slug' => 'budget-tee',
            'price' => 150,
            'quantity' => 2,
            'reserved_quantity' => 0,
            'description' => 'Lower priced item.',
            'seller_name' => 'Seller A',
            'seller_contact_number' => '09170000003',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ACTIVE,
        ]);

        Item::query()->create([
            'name' => 'Premium Coat',
            'slug' => 'premium-coat',
            'price' => 950,
            'quantity' => 1,
            'reserved_quantity' => 0,
            'description' => 'Higher priced item.',
            'seller_name' => 'Seller B',
            'seller_contact_number' => '09170000004',
            'condition' => ItemCondition::NEW,
            'status' => ItemStatus::ACTIVE,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.inventory.index', ['sort' => 'price_desc']))
            ->assertOk()
            ->assertSeeInOrder(['Premium Coat', 'Budget Tee']);
    }

    public function test_customer_home_filters_featured_items(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $matchingCategory = Category::query()->create([
            'name' => 'Jackets',
            'slug' => 'jackets',
            'description' => 'Outerwear',
            'is_active' => true,
        ]);

        $otherCategory = Category::query()->create([
            'name' => 'Shoes',
            'slug' => 'shoes',
            'description' => 'Footwear',
            'is_active' => true,
        ]);

        Item::query()->create([
            'category_id' => $matchingCategory->id,
            'name' => 'Denim Filter Jacket',
            'slug' => 'denim-filter-jacket',
            'price' => 699,
            'quantity' => 3,
            'reserved_quantity' => 0,
            'description' => 'Structured denim jacket.',
            'seller_name' => 'Seller A',
            'seller_contact_number' => '09170000001',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ACTIVE,
        ]);

        Item::query()->create([
            'category_id' => $otherCategory->id,
            'name' => 'Canvas Shoes',
            'slug' => 'canvas-shoes',
            'price' => 499,
            'quantity' => 2,
            'reserved_quantity' => 0,
            'description' => 'Everyday sneakers.',
            'seller_name' => 'Seller B',
            'seller_contact_number' => '09170000002',
            'condition' => ItemCondition::WORN,
            'status' => ItemStatus::ACTIVE,
        ]);

        $this
            ->actingAs($customer)
            ->get(route('customer.home', [
                'search' => 'Denim',
                'category_id' => $matchingCategory->id,
                'condition' => ItemCondition::GENTLY_USED->value,
            ]))
            ->assertOk()
            ->assertSee('Denim Filter Jacket')
            ->assertDontSee('Canvas Shoes');
    }

    public function test_customer_notifications_page_filters_by_search_and_read_state(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $firstReservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-CUSTOMER-0001',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::AFTERNOON->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 500,
        ]);

        $secondReservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-CUSTOMER-0002',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDays(2)->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->addDays(2),
            'total_amount' => 600,
        ]);

        $customer->notify(new ReservationStatusUpdatedNotification(
            $firstReservation,
            ReservationStatus::PENDING->label(),
            PaymentStatus::PENDING->label()
        ));

        $customer->notify(new ReservationStatusUpdatedNotification(
            $secondReservation,
            ReservationStatus::PENDING->label(),
            PaymentStatus::PENDING->label()
        ));

        $readNotification = $customer->notifications()->where('data->reference', 'RSV-CUSTOMER-0001')->firstOrFail();
        $readNotification->markAsRead();

        $this
            ->actingAs($customer)
            ->get(route('customer.notifications.index', [
                'search' => 'RSV-CUSTOMER-0002',
                'read_state' => 'unread',
            ]))
            ->assertOk()
            ->assertSee('RSV-CUSTOMER-0002')
            ->assertDontSee('RSV-CUSTOMER-0001');
    }
}
