<?php

namespace Database\Factories;

use App\Models\Odoo\OdooCompany;
use App\Models\Odoo\OdooPricelist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesIntentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'user_id' => User::factory(),
            'odoo_company_id' => OdooCompany::factory(),
            'sales_type' => fake()->regexify('[A-Za-z0-9]{10}'),
            'customer_name' => fake()->word(),
            'customer_phone' => fake()->regexify('[A-Za-z0-9]{30}'),
            'project_address' => fake()->text(),
            'odoo_pricelist_id' => OdooPricelist::factory(),
            'note' => fake()->text(),
            'state' => fake()->regexify('[A-Za-z0-9]{30}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
