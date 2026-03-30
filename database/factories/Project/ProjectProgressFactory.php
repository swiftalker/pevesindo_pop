<?php

namespace Database\Factories\Project;

use App\Models\Employee;
use App\Models\ProjectTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'project_task_id' => ProjectTask::factory(),
            'reported_by' => Employee::factory()->create()->reported_by,
            'progress_percentage' => fake()->randomNumber(),
            'notes' => fake()->text(),
            'photos' => '{}',
            'milestone_status' => fake()->regexify('[A-Za-z0-9]{30}'),
        ];
    }
}
