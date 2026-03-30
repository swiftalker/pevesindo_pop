<?php

namespace Database\Factories\Project;

use App\Models\OdooCompany;
use App\Models\OdooPartner;
use App\Models\SaleOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'sale_order_id' => SaleOrder::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'odoo_company_id' => OdooCompany::factory(),
            'odoo_partner_id' => OdooPartner::factory(),
            'date_start' => fake()->date(),
            'date_end' => fake()->date(),
            'project_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
