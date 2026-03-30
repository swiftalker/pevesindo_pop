<?php

namespace Database\Factories\Project;

use App\Models\OdooCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class RabTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_company_id' => OdooCompany::factory(),
            'name' => fake()->name(),
            'description' => fake()->text(),
            'is_active' => fake()->boolean(),
        ];
    }
}
