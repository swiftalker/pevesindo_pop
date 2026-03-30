<?php

namespace Database\Factories\Odoo;

use Illuminate\Database\Eloquent\Factories\Factory;

class OdooProductFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'internal_reference' => fake()->regexify('[A-Za-z0-9]{50}'),
            'product_type' => fake()->regexify('[A-Za-z0-9]{20}'),
            'list_price' => fake()->randomFloat(2, 0, 9999999999999.99),
            'uom_name' => fake()->regexify('[A-Za-z0-9]{20}'),
            'categ_name' => fake()->word(),
            'is_active' => fake()->boolean(),
        ];
    }
}
