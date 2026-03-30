<?php

namespace Database\Factories;

use App\Models\Odoo\OdooCompany;
use App\Models\Odoo\OdooPartner;
use App\Models\Odoo\OdooPricelist;
use App\Models\Sales\SalesIntent;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'sales_intent_id' => SalesIntent::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'odoo_company_id' => OdooCompany::factory(),
            'odoo_partner_id' => OdooPartner::factory(),
            'odoo_pricelist_id' => OdooPricelist::factory(),
            'date_order' => fake()->date(),
            'amount_untaxed' => fake()->randomFloat(2, 0, 9999999999999.99),
            'amount_tax' => fake()->randomFloat(2, 0, 9999999999999.99),
            'amount_total' => fake()->randomFloat(2, 0, 9999999999999.99),
            'sale_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'payment_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'analytic_data' => '{}',
        ];
    }
}
