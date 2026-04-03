<?php

namespace Database\Factories\Odoo\Sales\Pricelist;

use Illuminate\Database\Eloquent\Factories\Factory;

class PricelistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'pricelist_id' => Odoo\Sales\Pricelist\Pricelist::factory(),
            'min_quantity' => fake()->randomFloat(2, 0, 9999999999999.99),
            'fixed_price' => fake()->randomFloat(2, 0, 9999999999999.99),
            'percent_price' => fake()->randomFloat(2, 0, 999.99),
        ];
    }
}
