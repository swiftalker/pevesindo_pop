<?php

namespace Database\Factories\Odoo\Finance\Accounting;

use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'company_id' => Odoo\Core\Company::factory(),
            'partner_id' => Odoo\Core\Partner::factory(),
            'team_id' => Odoo\Sales\Crm\Team::factory(),
            'analytic_account_id' => Odoo\Finance\Accounting\AnalyticAccount::factory(),
            'move_type' => fake()->regexify('[A-Za-z0-9]{20}'),
            'amount_total' => fake()->randomFloat(2, 0, 9999999999999.99),
            'state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'payment_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'note' => fake()->text(),
            'x_studio_many2many_field_4jv_1jeesssc3' => '{}',
        ];
    }
}
