<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PickupSlot;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReservationReadyNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_admin_can_mark_reservation_ready_for_pickup_and_customer_receives_database_notification(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-READY-0001',
            'status' => ReservationStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 450,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('admin.reservations.update-status', $reservation), [
                'status' => ReservationStatus::READY_FOR_PICKUP->value,
                'payment_status' => PaymentStatus::PENDING->value,
                'notes' => 'Please proceed to the cashier during pickup.',
            ]);

        $response->assertRedirect(route('admin.reservations.show', $reservation));

        $reservation->refresh();

        $this->assertSame(ReservationStatus::READY_FOR_PICKUP, $reservation->status);
        $this->assertSame(PaymentStatus::PENDING, $reservation->payment_status);

        $notification = $customer->notifications()->latest()->first();

        $this->assertNotNull($notification);
        $this->assertSame('Reservation ready for pickup', $notification->data['title']);
        $this->assertSame(ReservationStatus::READY_FOR_PICKUP->value, $notification->data['status']);
        $this->assertTrue($notification->data['is_ready_for_pickup']);
    }

    public function test_customer_can_view_notifications_and_mark_one_as_read(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-READY-0002',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::AFTERNOON->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 550,
            'notes' => 'Bring your reservation reference at pickup.',
        ]);

        $customer->notify(new ReservationStatusUpdatedNotification(
            $reservation,
            ReservationStatus::PENDING->label(),
            PaymentStatus::PENDING->label()
        ));

        $notification = $customer->notifications()->latest()->firstOrFail();

        $this
            ->actingAs($customer)
            ->get(route('customer.notifications.index'))
            ->assertOk()
            ->assertSee('Reservation ready for pickup')
            ->assertSee('RSV-READY-0002');

        $this
            ->actingAs($customer)
            ->patch(route('customer.notifications.read', $notification->id))
            ->assertRedirect();

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }
}