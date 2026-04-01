<?php

namespace Database\Factories\Odoo\Services\Project;

use App\Models\Odoo\Core\Odoo\Core\Company;
use App\Models\Odoo\Services\Project\Odoo\Services\Project\Project;
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
            'name' => fake()->name(),
        ];
    }
}
