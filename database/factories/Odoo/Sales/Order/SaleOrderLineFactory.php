<?php

namespace Database\Factories\Odoo\Sales\Order;

use Illuminate\Database\Eloquent\Factories\Factory;

class SaleOrderLineFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'order_id' => Odoo\Sales\Order\SaleOrder::factory(),
            'sequence' => fake()->numberBetween(-10000, 10000),
            'display_type' => fake()->regexify('[A-Za-z0-9]{20}'),
            'product_id' => Odoo\SupplyChain\Inventory\Product::factory(),
            'analytic_account_id' => Odoo\Finance\Accounting\AnalyticAccount::factory(),
            'name' => fake()->name(),
            'product_uom_qty' => fake()->randomFloat(2, 0, 9999999999999.99),
            'price_unit' => fake()->randomFloat(2, 0, 9999999999999.99),
            'price_subtotal' => fake()->randomFloat(2, 0, 9999999999999.99),
            'discount' => fake()->randomFloat(2, 0, 999.99),
            'tax_amount' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
