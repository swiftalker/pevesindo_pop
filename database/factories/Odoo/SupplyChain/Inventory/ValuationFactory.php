<?php

namespace Database\Factories\Odoo\SupplyChain\Inventory;

use App\Models\Odoo\SupplyChain\Inventory\Odoo\SupplyChain\Inventory\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ValuationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'product_id' => Odoo\SupplyChain\Inventory\Product::factory(),
            'quantity' => fake()->randomFloat(2, 0, 9999999999999.99),
            'value' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
