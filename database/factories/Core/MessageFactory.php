<?php

namespace Database\Factories\Core;

use App\Models\Core\Messageable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'messageable_id' => Messageable::factory(),
            'messageable_type' => fake()->regexify('[A-Za-z0-9]{100}'),
            'body' => fake()->text(),
            'is_internal' => fake()->boolean(),
        ];
    }
}
