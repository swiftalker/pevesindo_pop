<?php

namespace Database\Factories\Sales;

use App\Models\SalesIntent;
use Illuminate\Database\Eloquent\Factories\Factory;

class CrmLeadFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'sales_intent_id' => SalesIntent::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'expected_revenue' => fake()->randomFloat(2, 0, 9999999999999.99),
            'probability' => fake()->randomFloat(2, 0, 999.99),
            'stage' => fake()->regexify('[A-Za-z0-9]{30}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
