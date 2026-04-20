<?php

namespace Tests\Feature;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerBrowseDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('customer', 'web');
    }

    public function test_customer_home_renders_one_card_per_item_even_when_quantity_is_multiple(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $item = Item::query()->create([
            'name' => 'Single Card Item',
            'slug' => 'single-card-item',
            'price' => 89,
            'quantity' => 3,
            'reserved_quantity' => 0,
            'description' => 'Should appear once on the home screen.',
            'seller_name' => 'Shop Owner',
            'seller_contact_number' => '09123456789',
            'condition' => ItemCondition::NEW,
            'status' => ItemStatus::ACTIVE,
        ]);

        $response = $this
            ->actingAs($customer)
            ->get(route('customer.home'));

        $response->assertOk();
        $response->assertSee('3 available');

        $content = $response->getContent();

        $this->assertSame(1, substr_count($content, route('items.show', $item)));
        $this->assertSame(1, substr_count($content, 'Single Card Item'));
    }
}