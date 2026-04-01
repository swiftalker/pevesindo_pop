<?php

namespace Database\Factories\Odoo\SupplyChain\Inventory;

use App\Models\Odoo\SupplyChain\Inventory\Odoo\SupplyChain\Inventory\Picking;
use App\Models\Odoo\SupplyChain\Inventory\Odoo\SupplyChain\Inventory\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMoveFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'picking_id' => Odoo\SupplyChain\Inventory\Picking::factory(),
            'product_id' => Odoo\SupplyChain\Inventory\Product::factory(),
            'product_uom_qty' => fake()->randomFloat(2, 0, 9999999999999.99),
            'state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
