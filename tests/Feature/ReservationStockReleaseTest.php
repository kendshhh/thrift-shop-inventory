<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ItemCondition;
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

class ReservationStockReleaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_reserved_quantity_is_released_on_completion(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::factory()->create([
            'quantity' => 5,
            'reserved_quantity' => 0,
            'status' => ItemStatus::ACTIVE,
        ]);

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-STOCK-0001',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 100,
        ]);
        $reservation->reservationItems()->create([
            'item_id' => $item->id,
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
        ]);
        $item->increment('reserved_quantity', 2);
        $item->refresh();
        $this->assertSame(2, $item->reserved_quantity);

        $this->actingAs($admin)
            ->patch(route('admin.reservations.update-status', $reservation), [
                'status' => ReservationStatus::COMPLETED->value,
                'payment_status' => PaymentStatus::COMPLETED->value,
                'notes' => 'Picked up and paid.',
                'confirmation_text' => 'confirm',
            ])
            ->assertRedirect(route('admin.reservations.show', $reservation));

        $item->refresh();
        $this->assertSame(0, $item->reserved_quantity, 'Reserved quantity should be released on completion.');
    }
}
