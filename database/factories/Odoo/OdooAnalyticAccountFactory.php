<?php

namespace Database\Factories\Odoo;

use App\Models\OdooAnalyticPlan;
use App\Models\OdooCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

class OdooAnalyticAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'odoo_analytic_plan_id' => OdooAnalyticPlan::factory(),
            'odoo_company_id' => OdooCompany::factory(),
            'name' => fake()->name(),
            'code' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
