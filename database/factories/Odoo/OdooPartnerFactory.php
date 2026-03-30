<?php

namespace Database\Factories\Odoo;

use App\Models\OdooCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class OdooPartnerFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'odoo_company_id' => OdooCompany::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->regexify('[A-Za-z0-9]{30}'),
            'street' => fake()->streetName(),
            'city' => fake()->city(),
            'state_name' => fake()->word(),
            'zip' => fake()->postcode(),
            'partner_type' => fake()->regexify('[A-Za-z0-9]{20}'),
            'is_company' => fake()->boolean(),
        ];
    }
}
