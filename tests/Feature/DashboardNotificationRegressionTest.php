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

class DashboardNotificationRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_dashboard_does_not_duplicate_notifications(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::factory()->create([
            'quantity' => 1,
            'reserved_quantity' => 1,
            'status' => ItemStatus::ACTIVE,
        ]);

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-NOTIF-0001',
            'status' => ReservationStatus::OVERDUE,
            'payment_status' => PaymentStatus::OVERDUE,
            'pickup_date' => now()->subDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->subDay(),
            'total_amount' => 100,
        ]);
        $reservation->reservationItems()->create([
            'item_id' => $item->id,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        // First dashboard visit triggers notification
        $this->actingAs($admin)
            ->get(route('admin.dashboard'));
        $this->assertSame(1, $admin->unreadNotifications()->where('type', \App\Notifications\OverdueReservationNotification::class)->count());

        // Second dashboard visit should NOT duplicate
        $this->actingAs($admin)
            ->get(route('admin.dashboard'));
        $this->assertSame(1, $admin->unreadNotifications()->where('type', \App\Notifications\OverdueReservationNotification::class)->count(), 'Dashboard should not duplicate notifications.');
    }
}
