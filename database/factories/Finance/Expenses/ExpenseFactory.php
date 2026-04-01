<?php

namespace Database\Factories\Finance\Expenses;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'pop_app_ref' => fake()->uuid(),
            'employee_id' => Odoo\HR\Employee\Employee::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'company_id' => Odoo\Core\Company::factory(),
            'product_id' => Odoo\SupplyChain\Inventory\Product::factory(),
            'project_id' => Services\Project\Project::factory(),
            'analytic_account_id' => Odoo\Finance\Accounting\AnalyticAccount::factory(),
            'name' => fake()->name(),
            'payment_mode' => fake()->regexify('[A-Za-z0-9]{20}'),
            'total_amount' => fake()->randomFloat(2, 0, 9999999999999.99),
            'date' => fake()->date(),
            'expense_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
