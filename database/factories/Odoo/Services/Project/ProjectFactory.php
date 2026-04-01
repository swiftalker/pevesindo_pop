<?php

namespace Database\Factories\Odoo\Services\Project;

use App\Models\Odoo\Core\Odoo\Core\Company;
use App\Models\Odoo\Core\Odoo\Core\Partner;
use App\Models\Odoo\Finance\Accounting\Odoo\Finance\Accounting\AnalyticAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'company_id' => Odoo\Core\Company::factory(),
            'partner_id' => Odoo\Core\Partner::factory(),
            'analytic_account_id' => Odoo\Finance\Accounting\AnalyticAccount::factory(),
            'name' => fake()->name(),
        ];
    }
}
