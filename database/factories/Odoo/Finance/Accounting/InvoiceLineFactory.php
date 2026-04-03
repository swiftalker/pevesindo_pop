<?php

namespace Database\Factories\Odoo\Finance\Accounting;

use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceLineFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'invoice_id' => Odoo\Finance\Accounting\Invoice::factory(),
            'product_id' => Odoo\SupplyChain\Inventory\Product::factory(),
            'name' => fake()->name(),
            'quantity' => fake()->randomFloat(2, 0, 9999999999999.99),
            'price_unit' => fake()->randomFloat(2, 0, 9999999999999.99),
            'price_subtotal' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
