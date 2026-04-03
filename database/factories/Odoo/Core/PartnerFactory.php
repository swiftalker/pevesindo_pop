<?php

namespace Database\Factories\Odoo\Core;

use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'company_id' => \App\Models\Odoo\Core\Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->regexify('[A-Za-z0-9]{30}'),
            'is_company' => fake()->boolean(),
        ];
    }
}
