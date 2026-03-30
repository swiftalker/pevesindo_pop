<?php

namespace Database\Factories\Odoo;

use App\Models\OdooPricelist;
use App\Models\OdooProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class OdooPricelistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'odoo_pricelist_id' => OdooPricelist::factory(),
            'odoo_product_id' => OdooProduct::factory(),
            'min_quantity' => fake()->randomFloat(2, 0, 9999999999999.99),
            'fixed_price' => fake()->randomFloat(2, 0, 9999999999999.99),
            'percent_price' => fake()->randomFloat(2, 0, 999.99),
            'date_start' => fake()->date(),
            'date_end' => fake()->date(),
        ];
    }
}
