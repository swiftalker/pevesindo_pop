<?php

namespace Database\Factories;

use App\Models\Odoo\OdooProduct;
use App\Models\Project\Rab;
use Illuminate\Database\Eloquent\Factories\Factory;

class RabLineFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'rab_id' => Rab::factory(),
            'odoo_product_id' => OdooProduct::factory(),
            'description' => fake()->text(),
            'quantity' => fake()->randomFloat(2, 0, 9999999999.99),
            'unit_price' => fake()->randomFloat(2, 0, 9999999999999.99),
            'subtotal' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
