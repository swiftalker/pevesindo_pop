<?php

namespace Database\Factories\Project;

use App\Models\Employee;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'project_id' => Project::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'assigned_to' => Employee::factory()->create()->assigned_to,
            'deadline' => fake()->date(),
            'progress_percentage' => fake()->randomNumber(),
            'milestone_status' => fake()->regexify('[A-Za-z0-9]{30}'),
            'task_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
