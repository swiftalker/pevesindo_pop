<?php

namespace Database\Factories\Sales\Crm;

use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'pop_app_ref' => fake()->uuid(),
            'intent_id' => Sales\Intent\Intent::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'team_id' => Odoo\Sales\Crm\Team::factory(),
            'name' => fake()->name(),
            'expected_revenue' => fake()->randomFloat(2, 0, 9999999999999.99),
            'stage' => fake()->regexify('[A-Za-z0-9]{30}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
