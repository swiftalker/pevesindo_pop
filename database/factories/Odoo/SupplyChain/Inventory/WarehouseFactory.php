<?php

namespace Database\Factories\Odoo\SupplyChain\Inventory;

use App\Models\Odoo\Core\Odoo\Core\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'company_id' => Odoo\Core\Company::factory(),
            'name' => fake()->name(),
            'code' => fake()->regexify('[A-Za-z0-9]{10}'),
        ];
    }
}
