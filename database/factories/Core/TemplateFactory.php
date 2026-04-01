<?php

namespace Database\Factories\Core;

use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'type' => fake()->regexify('[A-Za-z0-9]{50}'),
            'content' => fake()->paragraphs(3, true),
            'is_active' => fake()->boolean(),
            'is_default' => fake()->boolean(),
        ];
    }
}
