<?php

namespace Database\Factories\Project;

use App\Models\Employee;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyReportFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'project_id' => Project::factory(),
            'surveyor_id' => Employee::factory(),
            'findings' => fake()->text(),
            'measurement_data' => '{}',
            'recommended_products' => '{}',
            'photos' => '{}',
            'submitted_at' => fake()->dateTime(),
        ];
    }
}
