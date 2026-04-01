<?php

namespace Database\Factories\Services\Project;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'pop_app_ref' => fake()->uuid(),
            'order_id' => Sales\Order\SaleOrder::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'company_id' => Odoo\Core\Company::factory(),
            'partner_id' => Odoo\Core\Partner::factory(),
            'analytic_account_id' => Odoo\Finance\Accounting\AnalyticAccount::factory(),
            'date_start' => fake()->date(),
            'date_end' => fake()->date(),
            'project_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
