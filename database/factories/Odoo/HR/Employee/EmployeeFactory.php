<?php

namespace Database\Factories\Odoo\HR\Employee;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'company_id' => Odoo\Core\Company::factory(),
            'department_id' => Odoo\HR\Department\Department::factory(),
            'name' => fake()->name(),
            'job_title' => fake()->word(),
        ];
    }
}
