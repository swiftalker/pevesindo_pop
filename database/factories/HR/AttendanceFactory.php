<?php

namespace Database\Factories\HR;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'check_in' => fake()->dateTime(),
            'check_out' => fake()->dateTime(),
            'worked_hours' => fake()->randomFloat(2, 0, 999.99),
        ];
    }
}
