<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OdooCompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'code' => fake()->regexify('[A-Za-z0-9]{10}'),
            'is_active' => fake()->boolean(),
        ];
    }
}
