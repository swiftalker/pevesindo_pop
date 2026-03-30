<?php

namespace Database\Factories;

use App\Models\Sales\SaleOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
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
            'invoice_type' => fake()->regexify('[A-Za-z0-9]{20}'),
            'amount_total' => fake()->randomFloat(2, 0, 9999999999999.99),
            'amount_residual' => fake()->randomFloat(2, 0, 9999999999999.99),
            'invoice_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
