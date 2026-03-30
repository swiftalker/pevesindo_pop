<?php

namespace Database\Factories;

use App\Models\Odoo\OdooProduct;
use App\Models\Sales\SaleOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleOrderLineFactory extends Factory
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
            'odoo_product_id' => OdooProduct::factory(),
            'name' => fake()->name(),
            'product_uom_qty' => fake()->randomFloat(2, 0, 9999999999.99),
            'price_unit' => fake()->randomFloat(2, 0, 9999999999999.99),
            'price_subtotal' => fake()->randomFloat(2, 0, 9999999999999.99),
            'discount' => fake()->randomFloat(2, 0, 999.99),
            'tax_amount' => fake()->randomFloat(2, 0, 9999999999999.99),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
