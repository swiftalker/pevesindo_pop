<?php

namespace Database\Factories\HR\Employee;

use App\Models\Odoo\Core\Odoo\Core\Company;
use App\Models\Odoo\HR\Department\Odoo\HR\Department\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'job_title' => fake()->word(),
            'company_id' => Odoo\Core\Company::factory(),
            'is_active' => fake()->boolean(),
        ];
    }
}
