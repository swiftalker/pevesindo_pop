<?php

namespace Database\Factories\Odoo\SupplyChain\Inventory;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'internal_reference' => fake()->regexify('[A-Za-z0-9]{50}'),
            'product_type' => fake()->regexify('[A-Za-z0-9]{20}'),
            'is_active' => fake()->boolean(),
        ];
    }
}
