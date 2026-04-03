<?php

namespace Database\Factories\Odoo\Services\FieldService;

use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'project_id' => Odoo\Services\Project\Project::factory(),
            'company_id' => Odoo\Core\Company::factory(),
            'partner_id' => Odoo\Core\Partner::factory(),
            'name' => fake()->name(),
            'is_fsm' => fake()->boolean(),
        ];
    }
}
