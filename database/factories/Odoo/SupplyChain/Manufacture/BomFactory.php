<?php

namespace Database\Factories\Odoo\SupplyChain\Manufacture;

use App\Models\Odoo\Core\Odoo\Core\Company;
use App\Models\Odoo\SupplyChain\Inventory\Odoo\SupplyChain\Inventory\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class BomFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'product_id' => Odoo\SupplyChain\Inventory\Product::factory(),
            'company_id' => Odoo\Core\Company::factory(),
            'code' => fake()->regexify('[A-Za-z0-9]{20}'),
            'product_qty' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
