<?php

namespace Database\Factories\Finance\Accounting;

use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'pop_app_ref' => fake()->uuid(),
            'order_id' => Sales\Order\SaleOrder::factory(),
            'project_id' => Services\Project\Project::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'invoice_type' => fake()->regexify('[A-Za-z0-9]{20}'),
            'team_id' => Odoo\Sales\Crm\Team::factory(),
            'analytic_account_id' => Odoo\Finance\Accounting\AnalyticAccount::factory(),
            'amount_total' => fake()->randomFloat(2, 0, 9999999999999.99),
            'invoice_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'note' => fake()->text(),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
