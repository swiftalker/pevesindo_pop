<?php

namespace Database\Factories\Services\Project;

use Illuminate\Database\Eloquent\Factories\Factory;

class RabLineFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'rab_id' => Services\Project\Rab::factory(),
            'odoo_invoice_line_id' => fake()->numberBetween(-10000, 10000),
            'sequence' => fake()->numberBetween(-10000, 10000),
            'display_type' => fake()->regexify('[A-Za-z0-9]{20}'),
            'product_id' => Odoo\SupplyChain\Inventory\Product::factory(),
            'name' => fake()->name(),
            'quantity' => fake()->randomFloat(2, 0, 9999999999999.99),
            'unit_price' => fake()->randomFloat(2, 0, 9999999999999.99),
            'subtotal' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
