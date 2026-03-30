<?php

namespace Database\Factories\Expenses;

use App\Models\Employee;
use App\Models\OdooCompany;
use App\Models\OdooProduct;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
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
            'odoo_product_id' => OdooProduct::factory(),
            'project_id' => Project::factory(),
            'name' => fake()->name(),
            'total_amount' => fake()->randomFloat(2, 0, 9999999999999.99),
            'date' => fake()->date(),
            'expense_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
