<?php

namespace Database\Factories\Odoo\SupplyChain\Inventory;

use App\Models\Odoo\Core\Odoo\Core\Company;
use App\Models\Odoo\Core\Odoo\Core\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

class PickingFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'company_id' => Odoo\Core\Company::factory(),
            'partner_id' => Odoo\Core\Partner::factory(),
            'name' => fake()->name(),
            'picking_type_code' => fake()->regexify('[A-Za-z0-9]{20}'),
            'state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
