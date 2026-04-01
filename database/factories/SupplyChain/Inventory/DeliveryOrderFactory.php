<?php

namespace Database\Factories\SupplyChain\Inventory;

use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'pop_app_ref' => fake()->uuid(),
            'order_id' => Sales\Order\SaleOrder::factory(),
            'fsm_task_id' => Services\FieldService\Task::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'partner_id' => Odoo\Core\Partner::factory(),
            'warehouse_id' => Odoo\SupplyChain\Inventory\Warehouse::factory(),
            'scheduled_date' => fake()->date(),
            'driver_id' => Odoo\HR\Employee\Employee::factory(),
            'vehicle_id' => Odoo\SupplyChain\Fleet\Vehicle::factory(),
            'delivery_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
