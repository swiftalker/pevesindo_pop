<?php

namespace Database\Factories\Sales\Intent;

use Illuminate\Database\Eloquent\Factories\Factory;

class IntentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'intent_id' => Sales\Intent\Intent::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'product_id' => Odoo\SupplyChain\Inventory\Product::factory(),
            'name' => fake()->name(),
            'quantity' => fake()->randomFloat(2, 0, 9999999999999.99),
            'price_unit' => fake()->randomFloat(2, 0, 9999999999999.99),
            'subtotal' => fake()->randomFloat(2, 0, 9999999999999.99),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
