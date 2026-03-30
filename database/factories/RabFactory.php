<?php

namespace Database\Factories;

use App\Models\Project\Project;
use App\Models\Sales\SalesIntent;
use Illuminate\Database\Eloquent\Factories\Factory;

class RabFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'project_id' => Project::factory(),
            'sales_intent_id' => SalesIntent::factory(),
            'total' => fake()->randomFloat(2, 0, 9999999999999.99),
            'project_duration_days' => fake()->numberBetween(-10000, 10000),
            'technician_needed' => fake()->numberBetween(-10000, 10000),
            'rab_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'submitted_at' => fake()->dateTime(),
            'approved_at' => fake()->dateTime(),
        ];
    }
}
