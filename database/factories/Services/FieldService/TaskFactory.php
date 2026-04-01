<?php

namespace Database\Factories\Services\FieldService;

use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'pop_app_ref' => fake()->uuid(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'company_id' => Odoo\Core\Company::factory(),
            'partner_id' => Odoo\Core\Partner::factory(),
            'project_id' => Services\Project\Project::factory(),
            'name' => fake()->name(),
            'assigned_to' => Odoo\HR\Employee\Employee::factory()->create()->assigned_to,
            'worksheet_result' => fake()->text(),
            'reschedule_requested_at' => fake()->dateTime(),
            'reschedule_reason' => fake()->text(),
            'reassignment_requested_at' => fake()->dateTime(),
            'reassignment_reason' => fake()->text(),
            'fsm_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
