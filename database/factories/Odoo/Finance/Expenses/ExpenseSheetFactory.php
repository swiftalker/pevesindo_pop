<?php

namespace Database\Factories\Odoo\Finance\Expenses;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseSheetFactory extends Factory
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
            'name' => fake()->name(),
            'state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
