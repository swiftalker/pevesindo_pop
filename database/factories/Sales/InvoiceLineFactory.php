<?php

namespace Database\Factories\Sales;

use App\Models\Invoice;
use App\Models\OdooProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceLineFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'invoice_id' => Invoice::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'odoo_product_id' => OdooProduct::factory(),
            'name' => fake()->name(),
            'quantity' => fake()->randomFloat(2, 0, 9999999999999.99),
            'price_unit' => fake()->randomFloat(2, 0, 9999999999999.99),
            'price_subtotal' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
