<?php

namespace Database\Factories;

use App\Models\Odoo\OdooCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class OdooAnalyticPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'odoo_company_id' => OdooCompany::factory(),
            'name' => fake()->name(),
        ];
    }
}
