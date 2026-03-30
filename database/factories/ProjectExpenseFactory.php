<?php

namespace Database\Factories;

use App\Models\HR\Employee;
use App\Models\Project\Project;
use App\Models\Project\ProjectTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'project_id' => Project::factory(),
            'project_task_id' => ProjectTask::factory(),
            'employee_id' => Employee::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'description' => fake()->text(),
            'amount' => fake()->randomFloat(2, 0, 9999999999999.99),
            'category' => fake()->regexify('[A-Za-z0-9]{30}'),
            'expense_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
