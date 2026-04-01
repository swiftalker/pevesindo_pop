<?php

namespace Database\Factories\Odoo\Finance\Accounting;

use App\Models\Odoo\Core\Odoo\Core\Company;
use App\Models\Odoo\Finance\Accounting\Odoo\Finance\Accounting\AnalyticPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnalyticAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'plan_id' => Odoo\Finance\Accounting\AnalyticPlan::factory(),
            'company_id' => Odoo\Core\Company::factory(),
            'name' => fake()->name(),
            'code' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
