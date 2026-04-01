<?php

namespace Database\Factories\Services\Timesheet;

use Illuminate\Database\Eloquent\Factories\Factory;

class TimelogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'pop_app_ref' => fake()->uuid(),
            'fsm_task_id' => Services\FieldService\Task::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'employee_id' => Odoo\HR\Employee\Employee::factory(),
            'name' => fake()->name(),
            'unit_amount' => fake()->randomFloat(2, 0, 999.99),
            'date' => fake()->date(),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
