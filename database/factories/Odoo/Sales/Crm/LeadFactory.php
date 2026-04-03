<?php

namespace Database\Factories\Odoo\Sales\Crm;

use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'company_id' => Odoo\Core\Company::factory(),
            'team_id' => Odoo\Sales\Crm\Team::factory(),
            'name' => fake()->name(),
            'expected_revenue' => fake()->randomFloat(2, 0, 9999999999999.99),
            'probability' => fake()->randomFloat(2, 0, 999.99),
            'stage' => fake()->regexify('[A-Za-z0-9]{30}'),
        ];
    }
}
