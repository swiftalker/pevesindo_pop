<?php

namespace Database\Factories\Services\Project;

use App\Models\Sales\Intent\Sales\Intent\Intent;
use App\Models\Services\Project\Odoo\Sales\Crm\Team;
use App\Models\Services\Project\Services\Project\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class RabFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'project_id' => Services\Project\Project::factory(),
            'intent_id' => Sales\Intent\Intent::factory(),
            'odoo_invoice_id' => fake()->numberBetween(-10000, 10000),
            'team_id' => Odoo\Sales\Crm\Team::factory(),
            'total' => fake()->randomFloat(2, 0, 9999999999999.99),
            'rab_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'note' => fake()->text(),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'x_studio_many2many_field_4jv_1jeesssc3' => '{}',
        ];
    }
}
