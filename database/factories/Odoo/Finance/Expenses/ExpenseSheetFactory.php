<?php

namespace Database\Factories\Odoo\Finance\Expenses;

use App\Models\Odoo\Core\Odoo\Core\Company;
use App\Models\Odoo\HR\Employee\Odoo\HR\Employee\Employee;
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
