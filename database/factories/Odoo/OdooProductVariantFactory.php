<?php

namespace Database\Factories\Odoo;

use App\Models\OdooProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class OdooProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'odoo_product_id' => OdooProduct::factory(),
            'name' => fake()->name(),
            'default_code' => fake()->regexify('[A-Za-z0-9]{50}'),
            'barcode' => fake()->regexify('[A-Za-z0-9]{50}'),
            'qty_available' => fake()->randomFloat(2, 0, 9999999999999.99),
            'virtual_available' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
