<?php

namespace Database\Factories\Fleet;

use App\Models\Employee;
use App\Models\Fleet\OdooVehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class OdometerLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'odoo_vehicle_id' => OdooVehicle::factory(),
            'driver_id' => Employee::factory(),
            'date' => fake()->date(),
            'value' => fake()->randomFloat(2, 0, 9999999999999.99),
            'unit' => fake()->regexify('[A-Za-z0-9]{5}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
