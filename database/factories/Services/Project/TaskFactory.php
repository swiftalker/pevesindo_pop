<?php

namespace Database\Factories\Services\Project;

use App\Models\Odoo\Core\Odoo\Core\Company;
use App\Models\Odoo\Core\Odoo\Core\Partner;
use App\Models\Odoo\HR\Employee\Odoo\HR\Employee\Employee;
use App\Models\Odoo\Services\Project\Odoo\Services\Project\Project;
use App\Models\Services\Project\Services\Project\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'project_id' => Services\Project\Project::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'assigned_to' => Odoo\HR\Employee\Employee::factory()->create()->assigned_to,
            'progress_percentage' => fake()->randomNumber(),
            'milestone_status' => fake()->regexify('[A-Za-z0-9]{30}'),
            'task_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
