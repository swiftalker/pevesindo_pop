<?php

namespace Database\Factories\HR;

use App\Models\HR\User;
use App\Models\OdooCompany;
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
            'odoo_company_id' => OdooCompany::factory(),
            'department' => fake()->word(),
            'is_active' => fake()->boolean(),
        ];
    }
}
