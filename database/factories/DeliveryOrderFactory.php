<?php

namespace Database\Factories;

use App\Models\HR\Employee;
use App\Models\Odoo\OdooPartner;
use App\Models\Odoo\OdooWarehouse;
use App\Models\Sales\SaleOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'sale_order_id' => SaleOrder::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'odoo_partner_id' => OdooPartner::factory(),
            'odoo_warehouse_id' => OdooWarehouse::factory(),
            'scheduled_date' => fake()->date(),
            'driver_id' => Employee::factory(),
            'delivery_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
