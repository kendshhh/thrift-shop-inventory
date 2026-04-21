<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'slug' => Str::slug($this->faker->unique()->words(3, true)),
            'price' => $this->faker->numberBetween(50, 500),
            'quantity' => $this->faker->numberBetween(1, 10),
            'reserved_quantity' => 0,
            'description' => $this->faker->sentence(),
            'seller_name' => $this->faker->name(),
            'seller_contact_number' => $this->faker->numerify('0917#######'),
            'condition' => $this->faker->randomElement(ItemCondition::cases()),
            'status' => ItemStatus::ACTIVE,
        ];
    }
}
