<?php

namespace Database\Factories\SupplyChain\Purchase;

use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderLineFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'pop_app_ref' => fake()->uuid(),
            'order_id' => SupplyChain\Purchase\PurchaseOrder::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'product_id' => Odoo\SupplyChain\Inventory\Product::factory(),
            'analytic_account_id' => Odoo\Finance\Accounting\AnalyticAccount::factory(),
            'name' => fake()->name(),
            'product_uom_qty' => fake()->randomFloat(2, 0, 9999999999999.99),
            'price_unit' => fake()->randomFloat(2, 0, 9999999999999.99),
            'price_subtotal' => fake()->randomFloat(2, 0, 9999999999999.99),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
