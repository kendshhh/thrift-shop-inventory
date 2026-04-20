<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\PickupSlot;
use App\Enums\ReservationStatus;
use App\Models\BrandingSetting;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\User;
use App\Support\Branding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BrandingPaymentDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Branding::flushCache();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('customer', 'web');
    }

    public function test_admin_can_save_branding_without_payment_fields(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.branding.edit'))
            ->post(route('admin.branding.update'), [
                '_method' => 'PUT',
                'brand_name' => 'Everdarling',
                'brand_tagline' => 'Curated thrift finds',
                'primary_color' => '#0EA5E9',
                'secondary_color' => '#2563EB',
            ]);

        $response->assertRedirect(route('admin.branding.edit'));
        $response->assertSessionHasNoErrors();

        $settings = BrandingSetting::query()->firstOrFail();

        $this->assertSame('Everdarling', $settings->brand_name);
        $this->assertSame('Curated thrift finds', $settings->brand_tagline);
        $this->assertNull($settings->logo_path);
    }

    public function test_admin_can_save_multiple_payment_entries_with_multiple_qr_codes(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.payments.edit'))
            ->post(route('admin.payments.update'), [
                '_method' => 'PUT',
                'name' => 'Maria Santos',
                'bank_name' => 'BDO',
                'bank_number' => '012345678901',
                'qr_codes' => [
                    $this->fakeQrUpload('bdo-1.png'),
                    $this->fakeQrUpload('bdo-2.png'),
                ],
            ]);

        $this
            ->actingAs($admin)
            ->from(route('admin.payments.edit'))
            ->post(route('admin.payments.update'), [
                '_method' => 'PUT',
                'name' => 'Everdarling Cashier',
                'bank_name' => 'GCash',
                'bank_number' => '09171234567',
                'qr_codes' => [
                    $this->fakeQrUpload('gcash.png'),
                ],
            ])
            ->assertRedirect(route('admin.payments.edit'))
            ->assertSessionHasNoErrors();

        $response->assertRedirect(route('admin.payments.edit'));
        $response->assertSessionHasNoErrors();

        $settings = BrandingSetting::query()->firstOrFail();

        $this->assertCount(2, $settings->payment_details);
        $this->assertSame('Maria Santos', $settings->payment_details[0]['name']);
        $this->assertSame('BDO', $settings->payment_details[0]['bank_name']);
        $this->assertCount(2, $settings->payment_details[0]['qr_image_paths']);
        $this->assertCount(1, $settings->payment_details[1]['qr_image_paths']);

        foreach ($settings->payment_details as $entry) {
            foreach ($entry['qr_image_paths'] as $path) {
                Storage::disk('public')->assertExists($path);
            }
        }

        $this
            ->actingAs($admin)
            ->get(route('admin.payments.edit'))
            ->assertOk()
            ->assertSee('Add Payment Detail')
            ->assertSee('Saved Payment Details')
            ->assertSee('Maria Santos')
            ->assertSee('BDO')
            ->assertSee('Everdarling Cashier')
            ->assertSee('Show QR Code');
    }

    public function test_admin_can_edit_and_delete_saved_payment_detail(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Storage::disk('public')->put('payment-details/bdo-qr.png', 'image');
        Storage::disk('public')->put('payment-details/gcash-qr.png', 'image');

        $id1 = (string) \Illuminate\Support\Str::uuid();
        $id2 = (string) \Illuminate\Support\Str::uuid();
        BrandingSetting::query()->create([
            'id' => 1,
            'brand_name' => 'Everdarling',
            'primary_color' => '#0EA5E9',
            'secondary_color' => '#2563EB',
            'payment_details' => [
                [
                    'id' => $id1,
                    'name' => 'Maria Santos',
                    'bank_name' => 'BDO',
                    'bank_number' => '012345678901',
                    'qr_image_paths' => ['payment-details/bdo-qr.png'],
                ],
                [
                    'id' => $id2,
                    'name' => 'Everdarling Cashier',
                    'bank_name' => 'GCash',
                    'bank_number' => '09171234567',
                    'qr_image_paths' => ['payment-details/gcash-qr.png'],
                ],
            ],
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.payments.edit', ['edit' => $id2]))
            ->assertOk()
            ->assertSee('Edit Payment Detail')
            ->assertSee('Update Payment Detail')
            ->assertSee('Everdarling Cashier');

        $this
            ->actingAs($admin)
            ->from(route('admin.payments.edit', ['edit' => $id2]))
            ->post(route('admin.payments.update'), [
                '_method' => 'PUT',
                'edit_id' => $id2,
                'name' => 'Front Desk Cashier',
                'bank_name' => 'GCash',
                'bank_number' => '09990001111',
                'existing_qr_paths' => ['payment-details/gcash-qr.png'],
            ])
            ->assertRedirect(route('admin.payments.edit'))
            ->assertSessionHasNoErrors();

        $settings = BrandingSetting::query()->firstOrFail();

        $this->assertSame('Maria Santos', $settings->payment_details[0]['name']);
        $this->assertSame('Front Desk Cashier', $settings->payment_details[1]['name']);
        $this->assertSame('09990001111', $settings->payment_details[1]['bank_number']);

        $this
            ->actingAs($admin)
            ->delete(route('admin.payments.destroy', $id1))
            ->assertRedirect(route('admin.payments.edit'));

        $settings->refresh();

        $this->assertCount(1, $settings->payment_details);
        $this->assertSame('Front Desk Cashier', $settings->payment_details[0]['name']);
        Storage::disk('public')->assertMissing('payment-details/bdo-qr.png');
    }

    public function test_payment_details_are_visible_on_public_item_and_reservation_pages(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('payment-details/bdo-qr.png', 'image');
        Storage::disk('public')->put('payment-details/gcash-qr.png', 'image');

        BrandingSetting::query()->create([
            'id' => 1,
            'brand_name' => 'Everdarling',
            'brand_tagline' => 'Curated thrift finds',
            'primary_color' => '#0EA5E9',
            'secondary_color' => '#2563EB',
            'payment_details' => [
                [
                    'name' => 'Maria Santos',
                    'bank_name' => 'BDO',
                    'bank_number' => '012345678901',
                    'qr_image_paths' => ['payment-details/bdo-qr.png'],
                ],
                [
                    'name' => 'Everdarling Cashier',
                    'bank_name' => 'GCash',
                    'bank_number' => '09171234567',
                    'qr_image_paths' => ['payment-details/gcash-qr.png'],
                ],
            ],
        ]);

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::query()->create([
            'name' => 'Vintage Jacket',
            'slug' => 'vintage-jacket',
            'price' => 899.00,
            'quantity' => 3,
            'reserved_quantity' => 0,
            'description' => 'Classic denim jacket.',
            'seller_name' => 'Ana Cruz',
            'seller_contact_number' => '09170000000',
            'condition' => 'gently_used',
            'status' => 'active',
        ]);

        $reservation = Reservation::query()->create([
            'user_id' => $customer->id,
            'status' => ReservationStatus::READY_FOR_PICKUP,
            'payment_status' => PaymentStatus::PENDING,
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_slot' => PickupSlot::MORNING->value,
            'total_amount' => 899.00,
        ]);

        ReservationItem::query()->create([
            'reservation_id' => $reservation->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'unit_price' => 899.00,
        ]);

        $branding = [
            'brand_name' => 'Everdarling',
            'brand_tagline' => 'Curated thrift finds',
            'primary_color' => '#0EA5E9',
            'secondary_color' => '#2563EB',
            'logo_path' => null,
            'logo_url' => null,
            'payment_details' => [
                [
                    'name' => 'Maria Santos',
                    'bank_name' => 'BDO',
                    'bank_number' => '012345678901',
                    'qr_image_paths' => ['payment-details/bdo-qr.png'],
                    'qr_images' => [
                        [
                            'path' => 'payment-details/bdo-qr.png',
                            'url' => Storage::disk('public')->url('payment-details/bdo-qr.png'),
                        ],
                    ],
                ],
                [
                    'name' => 'Everdarling Cashier',
                    'bank_name' => 'GCash',
                    'bank_number' => '09171234567',
                    'qr_image_paths' => ['payment-details/gcash-qr.png'],
                    'qr_images' => [
                        [
                            'path' => 'payment-details/gcash-qr.png',
                            'url' => Storage::disk('public')->url('payment-details/gcash-qr.png'),
                        ],
                    ],
                ],
            ],
        ];

        $welcomeHtml = view('welcome', [
            'branding' => $branding,
        ])->render();

        $this->assertStringContainsString('Manual Payment Details', $welcomeHtml);
        $this->assertStringContainsString('Maria Santos', $welcomeHtml);
        $this->assertStringContainsString('012345678901', $welcomeHtml);

        View::share('branding', $branding);

        $this->actingAs($customer);

        $this
            ->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Accepted Payment Details')
            ->assertSee('GCash');

        $this
            ->get(route('customer.reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Payment Details')
            ->assertSee('BDO')
            ->assertSee('012345678901');
    }

    private function fakeQrUpload(string $filename): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'qr-');

        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sWwaP8AAAAASUVORK5CYII=', true));

        return new UploadedFile($path, $filename, 'image/png', null, true);
    }
}