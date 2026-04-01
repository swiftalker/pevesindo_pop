<?php

namespace Database\Factories\Core;

use App\Models\Core\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject_id' => Subject::factory(),
            'subject_type' => fake()->regexify('[A-Za-z0-9]{100}'),
            'description' => fake()->text(),
            'properties' => '{}',
        ];
    }
}
