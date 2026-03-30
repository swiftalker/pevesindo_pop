<?php

namespace Database\Factories\Expenses;

use App\Models\Employee;
use App\Models\OdooCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseSheetFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'employee_id' => Employee::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'odoo_company_id' => OdooCompany::factory(),
            'name' => fake()->name(),
            'state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
