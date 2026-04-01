<?php

namespace Database\Factories\SupplyChain\Purchase;

use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'pop_app_ref' => fake()->uuid(),
            'project_id' => Services\Project\Project::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'partner_id' => Odoo\Core\Partner::factory(),
            'name' => fake()->name(),
            'amount_total' => fake()->randomFloat(2, 0, 9999999999999.99),
            'state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'note' => fake()->text(),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
