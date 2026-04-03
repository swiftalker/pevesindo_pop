<?php

namespace Database\Factories\Odoo\Services\Timesheet;

use Illuminate\Database\Eloquent\Factories\Factory;

class TimelogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'project_id' => Odoo\Services\Project\Project::factory(),
            'name' => fake()->name(),
            'unit_amount' => fake()->randomFloat(2, 0, 999999.99),
            'date' => fake()->date(),
        ];
    }
}
