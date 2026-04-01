<?php

namespace Database\Factories\SupplyChain\Inventory;

use App\Models\Odoo\Core\Odoo\Core\Partner;
use App\Models\Odoo\HR\Employee\Odoo\HR\Employee\Employee;
use App\Models\Odoo\SupplyChain\Fleet\Odoo\SupplyChain\Fleet\Vehicle;
use App\Models\Odoo\SupplyChain\Inventory\Odoo\SupplyChain\Inventory\Warehouse;
use App\Models\Sales\Order\Sales\Order\SaleOrder;
use App\Models\Services\FieldService\Services\FieldService\Task;
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
