<?php

namespace Database\Seeders;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\PickupSlot;
use App\Enums\ReservationStatus;
use App\Models\BrandingSetting;
use App\Models\Category;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\User;
use App\Support\Branding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class SampleShopDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            [
                'name' => 'Tops',
                'slug' => 'tops',
                'description' => 'Shirts, blouses, and everyday upper-wear pieces.',
            ],
            [
                'name' => 'Bottoms',
                'slug' => 'bottoms',
                'description' => 'Jeans, trousers, skirts, and casual bottoms.',
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'description' => 'Bags, hats, jewelry, and styling extras.',
            ],
            [
                'name' => 'Dresses',
                'slug' => 'dresses',
                'description' => 'One-piece looks for casual days and dressier moments.',
            ],
            [
                'name' => 'Footwear',
                'slug' => 'footwear',
                'description' => 'Shoes and sandals to complete thrifted outfits.',
            ],
        ])->mapWithKeys(function (array $category): array {
            $model = Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                ]
            );

            return [$category['slug'] => $model];
        });

        $items = [
            [
                'slug' => 'linen-button-shirt',
                'category_slug' => 'tops',
                'name' => 'Linen Button Shirt',
                'price' => 289.00,
                'quantity' => 4,
                'description' => 'Breathable neutral button-down ideal for casual and office wear.',
                'seller_name' => 'Everdarling Curator',
                'seller_contact_number' => '09171234567',
                'condition' => ItemCondition::GENTLY_USED->value,
                'status' => ItemStatus::ACTIVE->value,
                'tags' => ['linen', 'neutral', 'daily-wear'],
                'image_path' => '/images/sample/linen-button-shirt.svg',
            ],
            [
                'slug' => 'denim-wide-leg-jeans',
                'category_slug' => 'bottoms',
                'name' => 'Denim Wide-Leg Jeans',
                'price' => 349.00,
                'quantity' => 3,
                'description' => 'High-rise denim with a relaxed silhouette and structured feel.',
                'seller_name' => 'Everdarling Curator',
                'seller_contact_number' => '09171234567',
                'condition' => ItemCondition::GENTLY_USED->value,
                'status' => ItemStatus::ACTIVE->value,
                'tags' => ['denim', 'streetwear', 'high-rise'],
                'image_path' => '/images/sample/denim-wide-leg-jeans.svg',
            ],
            [
                'slug' => 'canvas-tote-bag',
                'category_slug' => 'accessories',
                'name' => 'Canvas Tote Bag',
                'price' => 189.00,
                'quantity' => 5,
                'description' => 'Sturdy everyday tote with roomy interior and minimalist styling.',
                'seller_name' => 'Everdarling Curator',
                'seller_contact_number' => '09171234567',
                'condition' => ItemCondition::NEW->value,
                'status' => ItemStatus::ACTIVE->value,
                'tags' => ['bag', 'canvas', 'minimal'],
                'image_path' => '/images/sample/canvas-tote-bag.svg',
            ],
            [
                'slug' => 'striped-knit-cardigan',
                'category_slug' => 'tops',
                'name' => 'Striped Knit Cardigan',
                'price' => 259.00,
                'quantity' => 2,
                'description' => 'Soft knit cardigan with light structure and vintage-inspired stripes.',
                'seller_name' => 'Everdarling Curator',
                'seller_contact_number' => '09171234567',
                'condition' => ItemCondition::GENTLY_USED->value,
                'status' => ItemStatus::ACTIVE->value,
                'tags' => ['knit', 'layering', 'vintage'],
                'image_path' => '/images/sample/striped-knit-cardigan.svg',
            ],
            [
                'slug' => 'pleated-midi-skirt',
                'category_slug' => 'bottoms',
                'name' => 'Pleated Midi Skirt',
                'price' => 319.00,
                'quantity' => 3,
                'description' => 'Flowy midi skirt with crisp pleats and an easy-to-style silhouette.',
                'seller_name' => 'Everdarling Curator',
                'seller_contact_number' => '09171234567',
                'condition' => ItemCondition::NEW->value,
                'status' => ItemStatus::ACTIVE->value,
                'tags' => ['skirt', 'pleated', 'feminine'],
                'image_path' => '/images/sample/pleated-midi-skirt.svg',
            ],
        ];

        $seededItems = collect();

        foreach ($items as $item) {
            $seededItems->put($item['slug'], Item::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'category_id' => $categories[$item['category_slug']]->id,
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'description' => $item['description'],
                    'seller_name' => $item['seller_name'],
                    'seller_contact_number' => $item['seller_contact_number'],
                    'condition' => $item['condition'],
                    'status' => $item['status'],
                    'tags' => $item['tags'],
                    'image_path' => $item['image_path'],
                ]
            ));
        }

        $admin = User::query()->where('email', env('DEFAULT_ADMIN_EMAIL', 'admin@thriftshop.local'))->first();
        $customer = User::query()->where('email', env('DEFAULT_CUSTOMER_EMAIL', 'customer@thriftshop.local'))->first();

        $settings = BrandingSetting::query()->find(1) ?? BrandingSetting::query()->first() ?? tap(new BrandingSetting(), static function (BrandingSetting $settings): void {
            $settings->id = 1;
        });

        $existingPaymentDetails = is_array($settings->payment_details) ? array_values($settings->payment_details) : [];

        $samplePaymentDetails = [
            [
                'id' => 'seed-gcash',
                'name' => 'GCash',
                'bank_name' => 'GCash',
                'bank_number' => '09171234567',
                'qr_image_paths' => ['/images/sample/gcash-qr.svg'],
            ],
            [
                'id' => 'seed-bpi-transfer',
                'name' => 'BPI Transfer',
                'bank_name' => 'Bank of the Philippine Islands',
                'bank_number' => '1234-5678-90',
                'qr_image_paths' => ['/images/sample/bpi-qr.svg'],
            ],
        ];

        foreach ($samplePaymentDetails as $paymentDetail) {
            $matchIndex = collect($existingPaymentDetails)->search(static function (array $detail) use ($paymentDetail): bool {
                return ($detail['id'] ?? null) === $paymentDetail['id'];
            });

            if ($matchIndex === false) {
                $existingPaymentDetails[] = $paymentDetail;
                continue;
            }

            $existingPaymentDetails[$matchIndex] = array_merge($existingPaymentDetails[$matchIndex], $paymentDetail);
        }

        $settings->fill([
            'brand_name' => $settings->brand_name ?: 'Everdarling',
            'brand_tagline' => $settings->brand_tagline ?: 'Curate by Alex',
            'primary_color' => $settings->primary_color ?: '#0EA5E9',
            'secondary_color' => $settings->secondary_color ?: '#2563EB',
            'payment_details' => array_values($existingPaymentDetails),
            'updated_by' => $admin?->id,
        ]);

        $settings->save();

        if ($customer !== null) {
            $this->seedReservations($customer, $seededItems);
            $this->syncReservedQuantities();
        }

        Branding::flushCache();
    }

    private function seedReservations(User $customer, Collection $seededItems): void
    {
        $reservations = [
            [
                'reference' => 'RSV-SEED-1001',
                'status' => ReservationStatus::READY_FOR_PICKUP,
                'payment_status' => PaymentStatus::PENDING,
                'pickup_date' => now()->addDay()->toDateString(),
                'pickup_slot' => PickupSlot::MIDDAY->value,
                'expires_at' => now()->addDays(2),
                'paid_at' => null,
                'completed_at' => null,
                'notes' => 'Demo reservation for pickup workflow preview.',
                'item_slug' => 'pleated-midi-skirt',
                'quantity' => 1,
            ],
            [
                'reference' => 'RSV-SEED-1002',
                'status' => ReservationStatus::OVERDUE,
                'payment_status' => PaymentStatus::OVERDUE,
                'pickup_date' => now()->subDay()->toDateString(),
                'pickup_slot' => PickupSlot::AFTERNOON->value,
                'expires_at' => now()->subHours(12),
                'paid_at' => null,
                'completed_at' => null,
                'notes' => 'Demo overdue reservation that needs admin attention.',
                'item_slug' => 'striped-knit-cardigan',
                'quantity' => 1,
            ],
            [
                'reference' => 'RSV-SEED-1003',
                'status' => ReservationStatus::COMPLETED,
                'payment_status' => PaymentStatus::COMPLETED,
                'pickup_date' => now()->subDays(2)->toDateString(),
                'pickup_slot' => PickupSlot::MORNING->value,
                'expires_at' => now()->subDays(2),
                'paid_at' => now()->subDays(2),
                'completed_at' => now()->subDays(2),
                'notes' => 'Demo completed reservation for history and payment metrics.',
                'item_slug' => 'linen-button-shirt',
                'quantity' => 1,
            ],
        ];

        foreach ($reservations as $entry) {
            $item = $seededItems->get($entry['item_slug']);

            if (! $item instanceof Item) {
                continue;
            }

            $reservation = Reservation::query()->updateOrCreate(
                ['reference' => $entry['reference']],
                [
                    'user_id' => $customer->id,
                    'status' => $entry['status']->value,
                    'payment_status' => $entry['payment_status']->value,
                    'pickup_date' => $entry['pickup_date'],
                    'pickup_slot' => $entry['pickup_slot'],
                    'expires_at' => $entry['expires_at'],
                    'paid_at' => $entry['paid_at'],
                    'completed_at' => $entry['completed_at'],
                    'notes' => $entry['notes'],
                    'total_amount' => (float) $item->price * $entry['quantity'],
                ]
            );

            ReservationItem::query()->updateOrCreate(
                [
                    'reservation_id' => $reservation->id,
                    'item_id' => $item->id,
                ],
                [
                    'quantity' => $entry['quantity'],
                    'unit_price' => $item->price,
                    'line_total' => (float) $item->price * $entry['quantity'],
                ]
            );

            ReservationItem::query()
                ->where('reservation_id', $reservation->id)
                ->where('item_id', '!=', $item->id)
                ->delete();
        }
    }

    private function syncReservedQuantities(): void
    {
        $reservedQuantities = ReservationItem::query()
            ->selectRaw('reservation_items.item_id, SUM(reservation_items.quantity) as reserved_total')
            ->join('reservations', 'reservations.id', '=', 'reservation_items.reservation_id')
            ->whereIn('reservations.status', ReservationStatus::activeValues())
            ->groupBy('reservation_items.item_id')
            ->pluck('reserved_total', 'reservation_items.item_id');

        Item::query()->each(function (Item $item) use ($reservedQuantities): void {
            $item->forceFill([
                'reserved_quantity' => (int) ($reservedQuantities[$item->id] ?? 0),
            ])->save();
        });
    }
}