<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PickupSlot;
use App\Enums\ReservationStatus;
use App\Models\BrandingSetting;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminDashboardPaymentInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_dashboard_surfaces_payment_workflow_metrics_and_actions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        BrandingSetting::query()->create([
            'id' => 1,
            'brand_name' => 'Everdarling',
            'primary_color' => '#0EA5E9',
            'secondary_color' => '#2563EB',
            'payment_details' => [
                [
                    'name' => 'Front Desk Cashier',
                    'bank_name' => 'GCash',
                    'bank_number' => '09171234567',
                    'qr_image_paths' => [],
                ],
            ],
        ]);

        Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-DASH-0001',
            'status' => ReservationStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'expires_at' => now()->addDay(),
            'total_amount' => 200,
        ]);

        Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-DASH-0002',
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDays(2)->toDateString(),
            'pickup_slot' => PickupSlot::AFTERNOON->value,
            'expires_at' => now()->addDays(2),
            'total_amount' => 350,
        ]);

        Reservation::query()->create([
            'user_id' => $customer->id,
            'reference' => 'RSV-DASH-0003',
            'status' => ReservationStatus::COMPLETED,
            'payment_status' => PaymentStatus::COMPLETED,
            'pickup_date' => now()->subDay()->toDateString(),
            'pickup_slot' => PickupSlot::AFTERNOON->value,
            'expires_at' => now()->subDay(),
            'paid_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
            'total_amount' => 500,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Add New Item')
            ->assertSee('Payment Workflow')
            ->assertSee('Awaiting Payment')
            ->assertSee('Ready for Pickup')
            ->assertSee('Completed Payments')
            ->assertSee('Manage Payments')
            ->assertSee('Review Unpaid Reservations')
            ->assertSee(route('admin.inventory.create'), false)
            ->assertSee('1 payment detail entry published');
    }
}