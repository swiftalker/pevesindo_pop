<?php

namespace Database\Factories\Odoo\Finance\Expenses;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'employee_id' => Odoo\HR\Employee\Employee::factory(),
            'company_id' => Odoo\Core\Company::factory(),
            'product_id' => Odoo\SupplyChain\Inventory\Product::factory(),
            'analytic_account_id' => Odoo\Finance\Accounting\AnalyticAccount::factory(),
            'name' => fake()->name(),
            'payment_mode' => fake()->regexify('[A-Za-z0-9]{20}'),
            'total_amount' => fake()->randomFloat(2, 0, 9999999999999.99),
            'date' => fake()->date(),
            'state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
