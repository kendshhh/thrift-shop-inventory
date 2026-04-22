<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\PickupSlot;
use App\Enums\ReservationStatus;
use App\Models\Cart;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminReservationOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Ensure roles exist for tests
        if (!Role::where('name', 'admin')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'admin', 'guard_name' => 'web']);
        }
        if (!Role::where('name', 'customer')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'customer', 'guard_name' => 'web']);
        }
    }

    public function test_admin_can_view_overview_with_cart_hold_signals(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::factory()->create([
            'name' => 'Vintage Jacket',
            'status' => ItemStatus::ACTIVE,
            'quantity' => 8,
            'reserved_quantity' => 0,
        ]);

        $cart = Cart::query()->create([
            'user_id' => $customer->id,
            'expires_at' => now()->addMinutes(15),
        ]);

        $cart->items()->create([
            'item_id' => $item->id,
            'quantity' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reservations.overview'))
            ->assertOk()
            ->assertSee('Item Reservation Overview')
            ->assertSee('Vintage Jacket')
            ->assertSee('Cart Hold');
    }

    public function test_admin_can_remove_reserved_item_and_stock_is_released(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::factory()->create([
            'status' => ItemStatus::ACTIVE,
            'quantity' => 5,
            'reserved_quantity' => 0,
            'price' => 100,
        ]);

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-OVERVIEW-1001',
            'status' => ReservationStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::MIDDAY->value,
            'expires_at' => now()->addHours(24),
            'total_amount' => 200,
        ]);

        $reservationItem = $reservation->reservationItems()->create([
            'item_id' => $item->id,
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        $item->increment('reserved_quantity', 2);

        $this->actingAs($admin)
            ->patch(route('admin.reservations.remove-item', [$reservation, $reservationItem]), [
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect();

        $item->refresh();
        $reservation->refresh();

        $this->assertSame(0, $item->reserved_quantity);
        $this->assertSame(ReservationStatus::EXPIRED, $reservation->status);
    }

    public function test_admin_can_cancel_all_for_user_from_overview(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::factory()->create([
            'status' => ItemStatus::ACTIVE,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'price' => 60,
        ]);

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-OVERVIEW-2001',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::AFTERNOON->value,
            'expires_at' => now()->addHours(20),
            'total_amount' => 180,
        ]);

        $reservation->reservationItems()->create([
            'item_id' => $item->id,
            'quantity' => 3,
            'unit_price' => 60,
            'line_total' => 180,
        ]);

        $item->increment('reserved_quantity', 3);

        $this->actingAs($admin)
            ->patch(route('admin.reservations.cancel-all-for-user', $customer), [
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect();

        $item->refresh();
        $reservation->refresh();

        $this->assertSame(0, $item->reserved_quantity);
        $this->assertSame(ReservationStatus::EXPIRED, $reservation->status);
        $this->assertSame(PaymentStatus::OVERDUE, $reservation->payment_status);
    }
}
