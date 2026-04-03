<?php

namespace Database\Factories\Odoo\HR\Attendance;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'employee_id' => Odoo\HR\Employee\Employee::factory(),
            'check_in' => fake()->dateTime(),
            'check_out' => fake()->dateTime(),
            'worked_hours' => fake()->randomFloat(2, 0, 999.99),
        ];
    }
}
