<?php

namespace Database\Factories\Odoo\Services\Planning;

use Illuminate\Database\Eloquent\Factories\Factory;

class SlotFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'company_id' => Odoo\Core\Company::factory(),
            'start_datetime' => fake()->dateTime(),
            'end_datetime' => fake()->dateTime(),
            'state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
