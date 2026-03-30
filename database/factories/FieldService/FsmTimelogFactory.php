<?php

namespace Database\Factories\FieldService;

use App\Models\Employee;
use App\Models\FieldService\FsmTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class FsmTimelogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'fsm_task_id' => FsmTask::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'employee_id' => Employee::factory(),
            'name' => fake()->name(),
            'unit_amount' => fake()->randomFloat(2, 0, 999.99),
            'date' => fake()->date(),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
