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
use App\Notifications\AdminReservationNotification;
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

    public function test_customer_can_mark_all_notifications_as_read(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $firstReservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-READY-0101',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 300,
        ]);

        $secondReservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-READY-0102',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDays(2)->toDateString(),
            'pickup_slot' => PickupSlot::AFTERNOON->value,
            'expires_at' => now()->addDays(2),
            'total_amount' => 450,
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

        $this->assertSame(2, $customer->unreadNotifications()->count());

        $this
            ->actingAs($customer)
            ->patch(route('customer.notifications.mark-all-read'))
            ->assertRedirect();

        $this->assertSame(0, $customer->fresh()->unreadNotifications()->count());
        $this->assertSame(2, $customer->fresh()->notifications()->whereNotNull('read_at')->count());
    }

    public function test_customer_reservation_creation_creates_customer_and_admin_notifications(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::query()->create([
            'name' => 'Vintage Tee',
            'slug' => 'vintage-tee',
            'price' => 299,
            'quantity' => 4,
            'reserved_quantity' => 0,
            'description' => 'Soft cotton tee.',
            'seller_name' => 'Ana',
            'seller_contact_number' => '09170000000',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ACTIVE,
        ]);

        $this
            ->actingAs($customer)
            ->post(route('customer.reservations.store'), [
                'item_id' => $item->id,
                'quantity' => 1,
                'pickup_date' => now()->addDay()->toDateString(),
                'pickup_slot' => PickupSlot::MORNING->value,
                'notes' => 'Please hold until noon.',
            ])
            ->assertRedirect();

        $reservation = Reservation::query()->latest('id')->firstOrFail();

        $customerNotification = $customer->notifications()->latest()->first();
        $adminNotification = $admin->notifications()->latest()->first();

        $this->assertNotNull($customerNotification);
        $this->assertSame('Reservation submitted', $customerNotification->data['title']);
        $this->assertSame($reservation->reference, $customerNotification->data['reference']);

        $this->assertNotNull($adminNotification);
        $this->assertSame(AdminReservationNotification::class, $adminNotification->type);
        $this->assertSame('new_reservation', $adminNotification->data['event_type']);
        $this->assertSame($reservation->reference, $adminNotification->data['reference']);

        $this
            ->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('New reservation received')
            ->assertSee($reservation->reference);

        $this
            ->actingAs($admin)
            ->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee($reservation->reference)
            ->assertSee('New');

        $this
            ->actingAs($admin)
            ->get(route('admin.reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Reservation Details')
            ->assertSee('New');

        $adminNotification->refresh();

        $this->assertNotNull($adminNotification->read_at);

        $this
            ->actingAs($admin)
            ->get(route('admin.reservations.index'))
            ->assertOk()
            ->assertSee($reservation->reference)
            ->assertDontSee('Mark as Read');
    }

    public function test_customer_cannot_create_reservation_with_pickup_date_today(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::query()->create([
            'name' => 'Tomorrow Only Item',
            'slug' => 'tomorrow-only-item',
            'price' => 250,
            'quantity' => 3,
            'reserved_quantity' => 0,
            'description' => 'Pickup must be scheduled starting tomorrow.',
            'seller_name' => 'Ana',
            'seller_contact_number' => '09170000000',
            'condition' => ItemCondition::GENTLY_USED,
            'status' => ItemStatus::ACTIVE,
        ]);

        $this
            ->actingAs($customer)
            ->from(route('items.show', $item))
            ->post(route('customer.reservations.store'), [
                'item_id' => $item->id,
                'quantity' => 1,
                'pickup_date' => now()->toDateString(),
                'pickup_slot' => PickupSlot::MORNING->value,
            ])
            ->assertRedirect(route('items.show', $item))
            ->assertSessionHasErrors('pickup_date');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_customer_is_notified_when_admin_decides_cancellation_request(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-READY-0004',
            'status' => ReservationStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 450,
        ]);

        $this
            ->actingAs($customer)
            ->patch(route('customer.reservations.request-cancellation', $reservation), [
                'request_reason' => 'Cannot make the pickup schedule.',
            ])
            ->assertRedirect(route('customer.reservations.show', $reservation));

        $this
            ->actingAs($admin)
            ->patch(route('admin.reservations.update-customer-request', $reservation), [
                'action' => 'approve',
                'admin_note' => 'Request approved by the shop team.',
            ])
            ->assertRedirect(route('admin.reservations.show', $reservation));

        $reservation->refresh();
        $notification = $customer->notifications()->latest()->first();

        $this->assertNotNull($notification);
        $this->assertSame('Cancellation request Approved', $notification->data['title']);
        $this->assertSame('approved', $notification->data['request_status']);
        $this->assertSame('Request approved by the shop team.', $notification->data['notes']);

        $this
            ->actingAs($customer)
            ->get(route('customer.notifications.index'))
            ->assertOk()
            ->assertSee('Cancellation request Approved')
            ->assertSee('Request approved by the shop team.');
    }

    public function test_customer_can_request_cancellation_for_ready_for_pickup_reservation(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-READY-0005',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::AFTERNOON->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 500,
        ]);

        $this
            ->actingAs($customer)
            ->patch(route('customer.reservations.request-cancellation', $reservation), [
                'request_reason' => 'I can no longer pick this up.',
            ])
            ->assertRedirect(route('customer.reservations.show', $reservation));

        $reservation->refresh();

        $this->assertSame('cancellation', $reservation->customer_request_type);
        $this->assertSame('pending', $reservation->customer_request_status);
        $this->assertSame('I can no longer pick this up.', $reservation->customer_request_reason);
    }

    public function test_customer_cannot_request_cancellation_for_completed_reservation(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-READY-0006',
            'status' => ReservationStatus::COMPLETED,
            'payment_status' => PaymentStatus::COMPLETED,
            'pickup_date' => now()->subDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->subDays(2),
            'completed_at' => now()->subDay(),
            'paid_at' => now()->subDay(),
            'total_amount' => 500,
        ]);

        $this
            ->actingAs($customer)
            ->from(route('customer.reservations.show', $reservation))
            ->patch(route('customer.reservations.request-cancellation', $reservation), [
                'request_reason' => 'Should not be allowed.',
            ])
            ->assertRedirect(route('customer.reservations.show', $reservation))
            ->assertSessionHasErrors([
                'customer_request' => 'Cancellation requests are available for active reservations that are not yet completed.',
            ]);

        $reservation->refresh();

        $this->assertNull($reservation->customer_request_type);
        $this->assertNull($reservation->customer_request_status);
    }

    public function test_customer_cannot_request_reschedule_with_pickup_date_today(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-READY-0007',
            'status' => ReservationStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDays(2)->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 500,
        ]);

        $this
            ->actingAs($customer)
            ->from(route('customer.reservations.show', $reservation))
            ->patch(route('customer.reservations.request-reschedule', $reservation), [
                'requested_pickup_date' => now()->toDateString(),
                'requested_pickup_slot' => PickupSlot::AFTERNOON->value,
                'request_reason' => 'Need a different time.',
            ])
            ->assertRedirect(route('customer.reservations.show', $reservation))
            ->assertSessionHasErrors('requested_pickup_date');

        $reservation->refresh();

        $this->assertNull($reservation->customer_request_type);
        $this->assertNull($reservation->customer_request_status);
        $this->assertNull($reservation->customer_requested_pickup_date);
    }

    public function test_admin_cannot_mark_payment_completed_before_reservation_is_completed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-READY-0003',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 450,
        ]);

        $this
            ->actingAs($admin)
            ->from(route('admin.reservations.show', $reservation))
            ->patch(route('admin.reservations.update-status', $reservation), [
                'status' => ReservationStatus::READY_FOR_PICKUP->value,
                'payment_status' => PaymentStatus::COMPLETED->value,
                'notes' => 'Tried to mark as paid too early.',
            ])
            ->assertRedirect(route('admin.reservations.show', $reservation))
            ->assertSessionHasErrors([
                'payment_status' => 'Ready for pickup reservations must stay pending until payment is collected in person.',
            ]);

        $reservation->refresh();

        $this->assertSame(ReservationStatus::READY_FOR_PICKUP, $reservation->status);
        $this->assertSame(PaymentStatus::PENDING, $reservation->payment_status);
        $this->assertNull($reservation->paid_at);
    }
}