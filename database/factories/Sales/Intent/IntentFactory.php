<?php

namespace Database\Factories\Sales\Intent;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'pop_app_ref' => fake()->uuid(),
            'user_id' => User::factory(),
            'company_id' => Odoo\Core\Company::factory(),
            'sales_type' => fake()->regexify('[A-Za-z0-9]{20}'),
            'intent_state' => fake()->regexify('[A-Za-z0-9]{30}'),
            'pipeline_stage' => fake()->regexify('[A-Za-z0-9]{50}'),
            'customer_name' => fake()->word(),
            'customer_phone' => fake()->regexify('[A-Za-z0-9]{30}'),
            'project_address' => fake()->text(),
            'pricelist_id' => Odoo\Sales\Pricelist\Pricelist::factory(),
            'expected_revenue' => fake()->randomFloat(2, 0, 9999999999999.99),
            'note' => fake()->text(),
            'odoo_lead_id' => fake()->numberBetween(-10000, 10000),
            'odoo_order_id' => fake()->numberBetween(-10000, 10000),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
