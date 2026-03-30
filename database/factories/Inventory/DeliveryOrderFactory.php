<?php

namespace Database\Factories\Inventory;

use App\Models\Employee;
use App\Models\Inventory\FsmTask;
use App\Models\Inventory\OdooVehicle;
use App\Models\OdooPartner;
use App\Models\OdooWarehouse;
use App\Models\SaleOrder;
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
            'fsm_task_id' => FsmTask::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'odoo_partner_id' => OdooPartner::factory(),
            'odoo_warehouse_id' => OdooWarehouse::factory(),
            'scheduled_date' => fake()->date(),
            'driver_id' => Employee::factory(),
            'odoo_vehicle_id' => OdooVehicle::factory(),
            'delivery_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
