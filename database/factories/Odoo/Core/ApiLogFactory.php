<?php

namespace Database\Factories\Odoo\Core;

use App\Models\Odoo\Core\Odoo\Core\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'method' => fake()->regexify('[A-Za-z0-9]{10}'),
            'endpoint' => fake()->word(),
            'request_payload' => '{}',
            'response_status' => fake()->numberBetween(-10000, 10000),
            'response_body' => fake()->text(),
            'duration_ms' => fake()->numberBetween(-10000, 10000),
            'company_id' => Odoo\Core\Company::factory(),
        ];
    }
}
