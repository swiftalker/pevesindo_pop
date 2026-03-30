<?php

namespace Database\Factories\FieldService;

use App\Models\Employee;
use App\Models\OdooCompany;
use App\Models\OdooPartner;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class FsmTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'odoo_company_id' => OdooCompany::factory(),
            'odoo_partner_id' => OdooPartner::factory(),
            'project_id' => Project::factory(),
            'name' => fake()->name(),
            'assigned_to' => Employee::factory()->create()->assigned_to,
            'planned_date_begin' => fake()->dateTime(),
            'planned_date_end' => fake()->dateTime(),
            'effective_hours' => fake()->randomFloat(2, 0, 999.99),
            'fsm_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
