<?php

namespace Database\Factories\Fleet;

use App\Models\OdooCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class OdooVehicleFactory extends Factory
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
            'license_plate' => fake()->regexify('[A-Za-z0-9]{20}'),
            'model' => fake()->word(),
            'is_active' => fake()->boolean(),
        ];
    }
}
