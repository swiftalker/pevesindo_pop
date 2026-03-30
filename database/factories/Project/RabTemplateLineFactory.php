<?php

namespace Database\Factories\Project;

use App\Models\OdooProduct;
use App\Models\Project\RabTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class RabTemplateLineFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'rab_template_id' => RabTemplate::factory(),
            'odoo_product_id' => OdooProduct::factory(),
            'description' => fake()->text(),
            'default_quantity' => fake()->randomFloat(2, 0, 9999999999999.99),
        ];
    }
}
