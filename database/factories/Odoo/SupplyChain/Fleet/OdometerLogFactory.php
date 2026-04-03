<?php

namespace Database\Factories\Odoo\SupplyChain\Fleet;

use Illuminate\Database\Eloquent\Factories\Factory;

class OdometerLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'vehicle_id' => Odoo\SupplyChain\Fleet\Vehicle::factory(),
            'date' => fake()->date(),
            'value' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
