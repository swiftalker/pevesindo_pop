<?php

namespace Database\Factories\Odoo\Sales\Order;

use Illuminate\Database\Eloquent\Factories\Factory;

class SaleOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'partner_id' => Odoo\Core\Partner::factory(),
            'company_id' => Odoo\Core\Company::factory(),
            'team_id' => Odoo\Sales\Crm\Team::factory(),
            'amount_total' => fake()->randomFloat(2, 0, 9999999999999.99),
            'state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'note' => fake()->text(),
        ];
    }
}
