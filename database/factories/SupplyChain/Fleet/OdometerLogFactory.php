<?php

namespace Database\Factories\SupplyChain\Fleet;

use App\Models\Odoo\HR\Employee\Odoo\HR\Employee\Employee;
use App\Models\Odoo\SupplyChain\Fleet\Odoo\SupplyChain\Fleet\Vehicle;
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
            'vehicle_id' => Odoo\SupplyChain\Fleet\Vehicle::factory(),
            'driver_id' => Odoo\HR\Employee\Employee::factory(),
            'date' => fake()->date(),
            'value' => fake()->randomFloat(2, 0, 9999999999999.99),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
