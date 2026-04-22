<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\PickupSlot;
use App\Enums\ReservationStatus;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_customer_can_add_view_and_remove_cart_items(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::factory()->create([
            'status' => ItemStatus::ACTIVE,
            'quantity' => 5,
            'reserved_quantity' => 0,
        ]);

        $this
            ->actingAs($customer)
            ->post(route('cart.add'), [
                'item_id' => $item->id,
                'quantity' => 2,
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('carts', [
            'user_id' => $customer->id,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'item_id' => $item->id,
            'quantity' => 2,
        ]);

        $this
            ->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Your Cart')
            ->assertSee($item->name)
            ->assertSee('Update Quantity')
            ->assertSee('Apply')
            ->assertSee('Checkout Cart as Reservation');

        $this
            ->actingAs($customer)
            ->patch(route('cart.update'), [
                'item_id' => $item->id,
                'quantity' => 4,
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'item_id' => $item->id,
            'quantity' => 4,
        ]);

        $this
            ->actingAs($customer)
            ->patch(route('cart.update'), [
                'item_id' => $item->id,
                'quantity' => 4,
                'adjustment' => 'decrement',
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'item_id' => $item->id,
            'quantity' => 3,
        ]);

        $this
            ->actingAs($customer)
            ->patch(route('cart.update'), [
                'item_id' => $item->id,
                'quantity' => 3,
                'adjustment' => 'increment',
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'item_id' => $item->id,
            'quantity' => 4,
        ]);

        $this
            ->actingAs($customer)
            ->post(route('cart.remove'), [
                'item_id' => $item->id,
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseMissing('cart_items', [
            'item_id' => $item->id,
        ]);
    }

    public function test_customer_cannot_add_more_than_available_quantity_to_cart(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::factory()->create([
            'status' => ItemStatus::ACTIVE,
            'quantity' => 3,
            'reserved_quantity' => 1,
        ]);

        $this
            ->actingAs($customer)
            ->from(route('items.show', $item))
            ->post(route('cart.add'), [
                'item_id' => $item->id,
                'quantity' => 3,
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('items.show', $item))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_customer_cannot_update_cart_item_beyond_available_quantity(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::factory()->create([
            'status' => ItemStatus::ACTIVE,
            'quantity' => 4,
            'reserved_quantity' => 1,
        ]);

        $this->actingAs($customer)->post(route('cart.add'), [
            'item_id' => $item->id,
            'quantity' => 1,
            'confirmation_text' => 'confirm',
        ]);

        $this
            ->actingAs($customer)
            ->from(route('cart.index'))
            ->patch(route('cart.update'), [
                'item_id' => $item->id,
                'quantity' => 4,
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('cart_items', [
            'item_id' => $item->id,
            'quantity' => 1,
        ]);
    }

    public function test_customer_can_see_cart_badge_count_in_navigation(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::factory()->create([
            'status' => ItemStatus::ACTIVE,
            'quantity' => 5,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($customer)->post(route('cart.add'), [
            'item_id' => $item->id,
            'quantity' => 3,
            'confirmation_text' => 'confirm',
        ]);

        $this
            ->actingAs($customer)
            ->get(route('items.index'))
            ->assertOk()
            ->assertSee('Cart')
            ->assertSee('3');
    }

    public function test_checkout_creates_a_reservation_from_cart_items(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $firstItem = Item::factory()->create([
            'status' => ItemStatus::ACTIVE,
            'quantity' => 5,
            'reserved_quantity' => 0,
            'price' => 120,
        ]);

        $secondItem = Item::factory()->create([
            'status' => ItemStatus::ACTIVE,
            'quantity' => 6,
            'reserved_quantity' => 1,
            'price' => 75,
        ]);

        $this->actingAs($customer)->post(route('cart.add'), [
            'item_id' => $firstItem->id,
            'quantity' => 2,
            'confirmation_text' => 'confirm',
        ]);

        $this->actingAs($customer)->post(route('cart.add'), [
            'item_id' => $secondItem->id,
            'quantity' => 3,
            'confirmation_text' => 'confirm',
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(route('cart.checkout'), [
                'pickup_date' => now()->addDay()->toDateString(),
                'pickup_slot' => PickupSlot::AFTERNOON->value,
                'notes' => 'Please prepare all pieces together.',
                'confirmation_text' => 'confirm',
            ]);

        $reservation = Reservation::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('customer.reservations.show', $reservation));

        $this->assertSame(ReservationStatus::PENDING, $reservation->status);
        $this->assertSame(PaymentStatus::PENDING, $reservation->payment_status);
        $this->assertSame('Please prepare all pieces together.', $reservation->notes);
        $this->assertEquals(465.0, (float) $reservation->total_amount);
        $this->assertCount(2, $reservation->reservationItems);

        $firstItem->refresh();
        $secondItem->refresh();

        $this->assertSame(2, $firstItem->reserved_quantity);
        $this->assertSame(4, $secondItem->reserved_quantity);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_checkout_is_blocked_when_customer_has_maximum_active_reservations(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::factory()->create([
            'status' => ItemStatus::ACTIVE,
            'quantity' => 4,
            'reserved_quantity' => 0,
        ]);

        Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-CART-0001',
            'status' => ReservationStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 200,
        ]);

        Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-CART-0002',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDays(2)->toDateString(),
            'pickup_slot' => PickupSlot::AFTERNOON->value,
            'expires_at' => now()->addDays(2),
            'total_amount' => 120,
        ]);

        $this->actingAs($customer)->post(route('cart.add'), [
            'item_id' => $item->id,
            'quantity' => 1,
            'confirmation_text' => 'confirm',
        ]);

        $this
            ->actingAs($customer)
            ->from(route('cart.index'))
            ->post(route('cart.checkout'), [
                'pickup_date' => now()->addDay()->toDateString(),
                'pickup_slot' => PickupSlot::MIDDAY->value,
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors('cart');

        $item->refresh();

        $this->assertSame(0, $item->reserved_quantity);
        $this->assertDatabaseCount('reservations', 2);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_checkout_still_succeeds_when_admin_role_is_missing(): void
    {
        Role::query()->where('name', 'admin')->where('guard_name', 'web')->delete();

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::factory()->create([
            'status' => ItemStatus::ACTIVE,
            'quantity' => 5,
            'reserved_quantity' => 0,
            'price' => 150,
        ]);

        $this->actingAs($customer)->post(route('cart.add'), [
            'item_id' => $item->id,
            'quantity' => 2,
            'confirmation_text' => 'confirm',
        ]);

        $response = $this
            ->actingAs($customer)
            ->post(route('cart.checkout'), [
                'pickup_date' => now()->addDay()->toDateString(),
                'pickup_slot' => PickupSlot::MORNING->value,
                'confirmation_text' => 'confirm',
            ]);

        $reservation = Reservation::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('customer.reservations.show', $reservation));
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('carts', 0);
    }
}
