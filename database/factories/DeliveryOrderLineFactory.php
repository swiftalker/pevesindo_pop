<?php

namespace Database\Factories;

use App\Models\Inventory\DeliveryOrder;
use App\Models\Odoo\OdooProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryOrderLineFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'delivery_order_id' => DeliveryOrder::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'odoo_product_id' => OdooProduct::factory(),
            'name' => fake()->name(),
            'product_uom_qty' => fake()->randomFloat(2, 0, 9999999999.99),
            'qty_done' => fake()->randomFloat(2, 0, 9999999999.99),
        ];
    }
}
